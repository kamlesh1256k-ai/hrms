<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Salary Calculator</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 24px; }
        .row { display: flex; gap: 16px; align-items: flex-end; flex-wrap: wrap; }
        .field { min-width: 220px; }
        label { display: block; margin-bottom: 4px; }
        input, select { width: 100%; padding: 8px; }
        .btn { padding: 8px 12px; cursor: pointer; margin-top: 8px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f5f5f5; }
    </style>
</head>
<body>
    <h2>Dynamic Salary Calculator</h2>
    <p><a href="<?php echo site_url('salary-structure'); ?>">Back to Admin</a></p>

    <form method="post" action="<?php echo site_url('salary-structure/calculate'); ?>">
        <div class="row">
            <div class="field">
                <label>CTC (Annual)</label>
                <input type="number" name="ctc" step="0.01" required value="<?php echo htmlspecialchars((string) ($input['ctc'] ?? '')); ?>">
            </div>
            <div class="field">
                <label>Basic %</label>
                <input type="number" name="basic_percentage" step="0.01" value="<?php echo htmlspecialchars((string) ($input['basic_percentage'] ?? 50)); ?>">
            </div>
            <div class="field">
                <label>Salary Structure</label>
                <select name="structure_id">
                    <?php foreach ($structures as $s): ?>
                        <option value="<?php echo (int) $s['id']; ?>" <?php echo ((int) ($input['structure_id'] ?? 1) === (int) $s['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($s['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label><input type="checkbox" name="is_pf_enabled" value="1" <?php echo !empty($input['is_pf_enabled']) ? 'checked' : ''; ?>> PF Enabled</label>
                <label><input type="checkbox" name="is_esic_enabled" value="1" <?php echo !empty($input['is_esic_enabled']) ? 'checked' : ''; ?>> ESIC Enabled</label>
                <button class="btn" type="submit">Calculate</button>
            </div>
        </div>
    </form>

    <?php if (!empty($result)): ?>
        <h3>Annual Breakdown</h3>
        <table>
            <tr><th>Basic</th><td><?php echo number_format($result['annual']['basic'], 2); ?></td></tr>
            <tr><th>HRA</th><td><?php echo number_format($result['annual']['hra'], 2); ?></td></tr>
            <tr><th>Conveyance</th><td><?php echo number_format($result['annual']['conveyance'], 2); ?></td></tr>
            <tr><th>Medical</th><td><?php echo number_format($result['annual']['medical'], 2); ?></td></tr>
            <tr><th>Special</th><td><?php echo number_format($result['annual']['special'], 2); ?></td></tr>
            <tr><th>Gross</th><td><?php echo number_format($result['annual']['gross'], 2); ?></td></tr>
            <tr><th>PF</th><td><?php echo number_format($result['annual']['pf'], 2); ?></td></tr>
            <tr><th>ESIC Employee</th><td><?php echo number_format($result['annual']['esic_employee'], 2); ?></td></tr>
            <tr><th>ESIC Employer</th><td><?php echo number_format($result['annual']['esic_employer'], 2); ?></td></tr>
            <tr><th>Gratuity</th><td><?php echo number_format($result['annual']['gratuity'], 2); ?></td></tr>
            <tr><th>Net Salary</th><td><?php echo number_format($result['annual']['net_salary'], 2); ?></td></tr>
        </table>

        <h3>Monthly Breakdown</h3>
        <table>
            <tr><th>Basic</th><td><?php echo number_format($result['monthly']['basic'], 2); ?></td></tr>
            <tr><th>HRA</th><td><?php echo number_format($result['monthly']['hra'], 2); ?></td></tr>
            <tr><th>Conveyance</th><td><?php echo number_format($result['monthly']['conveyance'], 2); ?></td></tr>
            <tr><th>Medical</th><td><?php echo number_format($result['monthly']['medical'], 2); ?></td></tr>
            <tr><th>Special</th><td><?php echo number_format($result['monthly']['special'], 2); ?></td></tr>
            <tr><th>Gross</th><td><?php echo number_format($result['monthly']['gross'], 2); ?></td></tr>
            <tr><th>PF</th><td><?php echo number_format($result['monthly']['pf'], 2); ?></td></tr>
            <tr><th>ESIC Employee</th><td><?php echo number_format($result['monthly']['esic_employee'], 2); ?></td></tr>
            <tr><th>ESIC Employer</th><td><?php echo number_format($result['monthly']['esic_employer'], 2); ?></td></tr>
            <tr><th>Gratuity</th><td><?php echo number_format($result['monthly']['gratuity'], 2); ?></td></tr>
            <tr><th>Net Salary</th><td><?php echo number_format($result['monthly']['net_salary'], 2); ?></td></tr>
        </table>

        <h3>JSON Output</h3>
        <pre><?php echo json_encode($result['annual'], JSON_PRETTY_PRINT); ?></pre>
    <?php endif; ?>
</body>
</html>
