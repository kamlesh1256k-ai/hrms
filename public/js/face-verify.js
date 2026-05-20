/**
 * FaceVerify.js — Local Face Comparison Library
 * Algorithm: Multi-metric image similarity using
 *   1. LBP (Local Binary Patterns) histogram comparison
 *   2. Grayscale intensity histogram intersection
 *   3. Skin-tone region weighted comparison
 * No CDN / internet dependency required.
 * Author: HRMS Internal
 */

const FaceVerify = (function () {

    // ─── Constants ────────────────────────────────────────────────────────────
    const RESIZE    = 128;   // normalise both images to 128×128
    const REGIONS   = 8;     // split image into REGIONS×REGIONS cells for LBP
    const LBP_BINS  = 256;   // 8-bit LBP → 256 bins
    const GRAY_BINS = 64;    // coarser grayscale histogram bins

    // ─── Core canvas helpers ──────────────────────────────────────────────────

    /** Draw an HTMLImageElement to an offscreen canvas and return its ImageData */
    function imgToData(img, size) {
        const c  = document.createElement('canvas');
        c.width  = size;
        c.height = size;
        const ctx = c.getContext('2d');
        ctx.drawImage(img, 0, 0, size, size);
        return ctx.getImageData(0, 0, size, size);
    }

    /** Convert RGBA ImageData to a flat Float32 grayscale array [0,1] */
    function toGray(data) {
        const pixels = data.data;
        const len    = data.width * data.height;
        const gray   = new Float32Array(len);
        for (let i = 0; i < len; i++) {
            const o = i * 4;
            // ITU-R BT.601 luma
            gray[i] = (0.299 * pixels[o] + 0.587 * pixels[o + 1] + 0.114 * pixels[o + 2]) / 255.0;
        }
        return gray;
    }

    // ─── LBP ─────────────────────────────────────────────────────────────────

    /**
     * Compute the 8-neighbour uniform LBP map for a grayscale image.
     * Returns a Uint8Array of LBP codes (0-255) same size as the image.
     */
    function computeLBP(gray, w, h) {
        const lbp = new Uint8Array(w * h);
        const dx  = [-1, 0, 1, 1, 1, 0, -1, -1];
        const dy  = [-1, -1, -1, 0, 1, 1, 1, 0];

        for (let y = 1; y < h - 1; y++) {
            for (let x = 1; x < w - 1; x++) {
                const center = gray[y * w + x];
                let code = 0;
                for (let b = 0; b < 8; b++) {
                    const nx = x + dx[b];
                    const ny = y + dy[b];
                    if (gray[ny * w + nx] >= center) {
                        code |= (1 << b);
                    }
                }
                lbp[y * w + x] = code;
            }
        }
        return lbp;
    }

    /**
     * Compute a combined LBP+grayscale histogram for the whole image,
     * split into REGIONS×REGIONS spatial cells.
     * Returns a concatenated Float32Array of normalised histograms.
     */
    function buildFeatureVector(gray, lbp, w, h) {
        const cellW  = Math.floor(w / REGIONS);
        const cellH  = Math.floor(h / REGIONS);
        const numCells = REGIONS * REGIONS;
        const vecLen   = numCells * (LBP_BINS + GRAY_BINS);
        const vec      = new Float32Array(vecLen);

        let offset = 0;
        for (let ry = 0; ry < REGIONS; ry++) {
            for (let rx = 0; rx < REGIONS; rx++) {
                const lbpHist  = new Float32Array(LBP_BINS);
                const grayHist = new Float32Array(GRAY_BINS);
                let count = 0;

                const x0 = rx * cellW;
                const y0 = ry * cellH;
                const x1 = x0 + cellW;
                const y1 = y0 + cellH;

                for (let py = y0; py < y1; py++) {
                    for (let px = x0; px < x1; px++) {
                        const idx  = py * w + px;
                        lbpHist[lbp[idx]]++;
                        const bin = Math.min(GRAY_BINS - 1, Math.floor(gray[idx] * GRAY_BINS));
                        grayHist[bin]++;
                        count++;
                    }
                }

                // Normalise
                if (count > 0) {
                    for (let i = 0; i < LBP_BINS; i++) {
                        vec[offset + i] = lbpHist[i] / count;
                    }
                    for (let i = 0; i < GRAY_BINS; i++) {
                        vec[offset + LBP_BINS + i] = grayHist[i] / count;
                    }
                }
                offset += LBP_BINS + GRAY_BINS;
            }
        }
        return vec;
    }

    // ─── Histogram similarity ─────────────────────────────────────────────────

    /**
     * Chi-square distance between two histograms (lower = more similar).
     * Returns value in [0, ∞). Typical face-match threshold ~0.15.
     */
    function chiSquare(a, b) {
        let dist = 0;
        for (let i = 0; i < a.length; i++) {
            const s = a[i] + b[i];
            if (s > 0) {
                const d = a[i] - b[i];
                dist += (d * d) / s;
            }
        }
        return dist / a.length;
    }

    /**
     * Histogram intersection similarity (higher = more similar, max 1).
     */
    function histIntersection(a, b) {
        let sum = 0;
        for (let i = 0; i < a.length; i++) {
            sum += Math.min(a[i], b[i]);
        }
        return sum;
    }

    // ─── Skin-tone boost ──────────────────────────────────────────────────────

    /**
     * Count skin-like pixels using simple YCbCr-style range.
     * Returns fraction [0,1] of pixels that appear to be skin.
     */
    function skinFraction(data) {
        const pixels = data.data;
        const total  = data.width * data.height;
        let skin     = 0;
        for (let i = 0; i < total; i++) {
            const o = i * 4;
            const r = pixels[o], g = pixels[o + 1], b = pixels[o + 2];
            // Rough skin range in RGB
            if (r > 95 && g > 40 && b > 20 &&
                r > g && r > b &&
                Math.abs(r - g) > 15 &&
                r - b > 15) {
                skin++;
            }
        }
        return skin / total;
    }

    // ─── Public API ───────────────────────────────────────────────────────────

    /**
     * Compare two HTMLImageElements.
     * Returns a Promise resolving to:
     *   { score: Number [0-100], verified: Boolean, label: String }
     */
    async function compare(imgA, imgB) {
        // Ensure images are loaded
        await Promise.all([
            new Promise(r => { if (imgA.complete) r(); else imgA.onload = r; }),
            new Promise(r => { if (imgB.complete) r(); else imgB.onload = r; }),
        ]);

        // — Step 1: Pixel data —
        const dataA = imgToData(imgA, RESIZE);
        const dataB = imgToData(imgB, RESIZE);

        // — Step 2: Grayscale —
        const grayA = toGray(dataA);
        const grayB = toGray(dataB);

        // — Step 3: LBP maps —
        const lbpA = computeLBP(grayA, RESIZE, RESIZE);
        const lbpB = computeLBP(grayB, RESIZE, RESIZE);

        // — Step 4: Feature vectors —
        const vecA = buildFeatureVector(grayA, lbpA, RESIZE, RESIZE);
        const vecB = buildFeatureVector(grayB, lbpB, RESIZE, RESIZE);

        // — Step 5: Chi-square distance (primary metric) —
        const chi   = chiSquare(vecA, vecB);
        // Map chi to [0,1] similarity: empirically chi<0.05 = same, chi>0.35 = different
        const lbpSim = Math.max(0, Math.min(1, 1 - chi / 0.35));

        // — Step 6: Histogram intersection (secondary metric) —
        const hiSim = histIntersection(vecA, vecB);   // already normalised [0,1]

        // — Step 7: Skin-tone presence (weights result) —
        const skinA = skinFraction(dataA);
        const skinB = skinFraction(dataB);
        // If both images have faces (skin fraction > 5%), give a bonus; otherwise penalise
        const skinWeight = (skinA > 0.05 && skinB > 0.05) ? 1.05 : 0.90;

        // — Step 8: Weighted final score —
        const raw   = (lbpSim * 0.65 + hiSim * 0.35) * skinWeight;
        const score = Math.round(Math.max(0, Math.min(100, raw * 100)));

        // Threshold: >= 55% = verified
        const verified = score >= 55;
        const label    = verified ? 'Verified Image' : 'Not Verified';

        return { score, verified, label, chi, lbpSim, hiSim };
    }

    return { compare };
})();
