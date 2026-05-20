/**
 * Tiny axios wrapper for the Sanctum REST API.
 * - Always sends Authorization: Bearer <token>
 * - 8-second timeout (network-flaky is the common case)
 */
const axios   = require('axios');
const fs      = require('fs');
const FormData= require('form-data');

function client(cfg) {
    return axios.create({
        baseURL: cfg.apiUrl,
        timeout: 8000,
        headers: {
            Authorization: `Bearer ${cfg.token}`,
            Accept:        'application/json',
        },
    });
}

async function post(cfg, path, body) {
    return client(cfg).post(path, body);
}

async function uploadFile(cfg, path, filePath, fieldName, extraFields = {}) {
    const fd = new FormData();
    fd.append(fieldName, fs.createReadStream(filePath));
    Object.entries(extraFields || {}).forEach(([k, v]) => fd.append(k, v == null ? '' : String(v)));
    return axios.post(cfg.apiUrl + path, fd, {
        timeout: 30000,
        headers: {
            ...fd.getHeaders(),
            Authorization: `Bearer ${cfg.token}`,
            Accept:        'application/json',
        },
        maxBodyLength: 50 * 1024 * 1024,
    });
}

module.exports = { post, uploadFile };
