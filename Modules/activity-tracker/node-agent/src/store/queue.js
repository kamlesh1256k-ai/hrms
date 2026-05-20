/**
 * Offline queue backed by sql.js — pure-JS SQLite, no native compile required.
 *
 * Trade-off vs better-sqlite3: sql.js holds the DB in memory and we flush to
 * disk after every mutation. For a small offline queue (hundreds of rows max
 * before sync drains it) this is perfectly fine and avoids the C++ toolchain
 * dependency on the agent machine.
 *
 * Schema:
 *   id INTEGER PRIMARY KEY AUTOINCREMENT
 *   kind TEXT NOT NULL                  -- 'activity' | 'screenshot' | 'app-usage'
 *   payload TEXT NOT NULL               -- JSON-serialised payload (file path for screenshots)
 *   created_at TEXT NOT NULL
 */
const path     = require('path');
const fs       = require('fs');
const { app }  = require('electron');

let db = null;
let dbPath = null;
let initSqlJs = null;

async function open() {
    if (db) return db;
    if (!initSqlJs) initSqlJs = require('sql.js');

    const SQL = await initSqlJs({
        // sql.js bundles a wasm file — point loader at the package's dist
        locateFile: (file) => path.join(require.resolve('sql.js/package.json'), '..', 'dist', file),
    });

    const dir = app.getPath('userData');
    if (!fs.existsSync(dir)) fs.mkdirSync(dir, { recursive: true });
    dbPath = path.join(dir, 'tracker-queue.sqlite');

    if (fs.existsSync(dbPath)) {
        const fileBuf = fs.readFileSync(dbPath);
        db = new SQL.Database(new Uint8Array(fileBuf));
    } else {
        db = new SQL.Database();
    }

    db.run(`
        CREATE TABLE IF NOT EXISTS queue (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            kind        TEXT NOT NULL,
            payload     TEXT NOT NULL,
            created_at  TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        );
        CREATE INDEX IF NOT EXISTS idx_queue_kind ON queue(kind);
    `);
    persist();
    return db;
}

function persist() {
    if (!db || !dbPath) return;
    try {
        const data = db.export();
        fs.writeFileSync(dbPath, Buffer.from(data));
    } catch (e) { /* swallow — disk full or perms */ }
}

async function enqueue(kind, payload) {
    const d = await open();
    d.run('INSERT INTO queue (kind, payload, created_at) VALUES (?, ?, ?)',
        [kind, JSON.stringify(payload || {}), new Date().toISOString()]);
    persist();
}

function size() {
    if (!db) return 0;
    const stmt = db.prepare('SELECT COUNT(*) AS c FROM queue');
    stmt.step();
    const row = stmt.getAsObject();
    stmt.free();
    return row.c || 0;
}

async function peek(limit = 50) {
    const d = await open();
    const stmt = d.prepare('SELECT id, kind, payload FROM queue ORDER BY id ASC LIMIT ?');
    stmt.bind([limit]);
    const out = [];
    while (stmt.step()) {
        const r = stmt.getAsObject();
        out.push({ id: r.id, kind: r.kind, payload: JSON.parse(r.payload) });
    }
    stmt.free();
    return out;
}

async function deleteRow(id) {
    const d = await open();
    d.run('DELETE FROM queue WHERE id = ?', [id]);
    persist();
}

module.exports = { enqueue, size, peek, delete: deleteRow, open };
