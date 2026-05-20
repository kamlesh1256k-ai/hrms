<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Salary Structure Admin</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 24px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f5f5f5; }
        .row { display: flex; gap: 16px; }
        .col { flex: 1; }
        input, select, textarea { width: 100%; padding: 8px; margin-top: 4px; margin-bottom: 8px; }
        .btn { padding: 8px 12px; cursor: pointer; text-decoration: none; border: 1px solid #333; background: #fff; display: inline-block; }
    </style>
</head>
<body>
    <h2>Dynamic Salary Component Admin</h2>
    <p><a class="btn" href="<?php echo site_url('salary-structure/calculate'); ?>">Open Salary Calculator</a></p>

    <div class="row">
        <div class="col">
            <h3>Available Structures</h3>
            <ul>
                <?php foreach ($structures as $s): ?>
                    <li><?php echo htmlspecialchars($s['name']); ?> (<?php echo htmlspecialchars($s['country']); ?>)</li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="col">
            <h3>Add New Component</h3>
            <form method="post" action="<?php echo site_url('salary-structure/component/save'); ?>">
                <label>Name</label>
                <input type="text" name="name" required>

                <label>Type</label>
                <select name="type" required>
                    <option value="earning">Earning</option>
                    <option value="deduction">Deduction</option>
                    <option value="employer">Employer</option>
                </select>

                <label>Calculation Type</label>
                <select name="calculation_type" required>
                    <option value="fixed">Fixed</option>
                    <option value="percentage">Percentage</option>
                    <option value="formula">Formula</option>
                </select>

                <label>Value</label>
                <input type="number" step="0.01" name="value" placeholder="For fixed/percentage">

                <label>Formula</label>
                <textarea name="formula" rows="2" placeholder="Example: BASIC * 0.5"></textarea>

                <label>Condition Rule</label>
                <textarea name="condition_rule" rows="2" placeholder="Example: (GROSS <= 252000) AND (ESIC_ENABLED == 1)"></textarea>

                <label><input type="checkbox" name="status" value="1" checked> Active</label><br>
                <button type="submit" class="btn">Save Component</button>
            </form>
        </div>
    </div>

    <h3>Components</h3>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Type</th>
                <th>Calc Type</th>
                <th>Value</th>
                <th>Formula</th>
                <th>Condition</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($components as $c): ?>
            <tr>
                <td><?php echo (int) $c['id']; ?></td>
                <td><?php echo htmlspecialchars($c['name']); ?></td>
                <td><?php echo htmlspecialchars($c['type']); ?></td>
                <td><?php echo htmlspecialchars($c['calculation_type']); ?></td>
                <td><?php echo htmlspecialchars((string) $c['value']); ?></td>
                <td><?php echo htmlspecialchars((string) $c['formula']); ?></td>
                <td><?php echo htmlspecialchars((string) $c['condition_rule']); ?></td>
                <td><?php echo (int) $c['status'] === 1 ? 'Active' : 'Inactive'; ?></td>
                <td>
                    <a class="btn" href="<?php echo site_url('salary-structure/component/delete/' . (int) $c['id']); ?>" onclick="return confirm('Delete component?')">Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
