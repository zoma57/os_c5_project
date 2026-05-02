<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>C5 | Premium OS Simulator</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { 
            --bg: #f0f4f8; --card: #ffffff; --text-main: #1e293b; --text-muted: #64748b;
            --primary: #2563eb; --primary-hover: #1d4ed8; 
            --secondary: #475569; --accent: #10b981; --accent-hover: #059669;
            --danger: #ef4444; --border: #e2e8f0; --warning: #f59e0b;
        }
        body { font-family: 'Poppins', sans-serif; background-color: var(--bg); color: var(--text-main); margin: 0; padding: 40px 20px; }
        .container { max-width: 1050px; margin: 0 auto; background: var(--card); padding: 40px; border-radius: 20px; box-shadow: 0 20px 40px -10px rgba(0,0,0,0.08); animation: fadeIn 0.6s ease-out; }
        
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

        h1 { text-align: center; font-weight: 700; color: #0f172a; margin-top: 0; margin-bottom: 30px; letter-spacing: -0.5px; }
        h1 span { color: var(--primary); }

        .presets { display: flex; gap: 10px; margin-bottom: 30px; flex-wrap: wrap; justify-content: center; background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px dashed #cbd5e1; align-items: center; }
        .presets strong { color: var(--secondary); font-weight: 600; margin-right: 5px; }
        .preset-btn { background: white; color: var(--secondary); border: 1px solid var(--border); padding: 8px 16px; border-radius: 50px; cursor: pointer; font-weight: 500; font-family: 'Poppins', sans-serif; transition: all 0.2s ease; box-shadow: 0 2px 4px rgba(0,0,0,0.02); font-size: 13px; }
        .preset-btn:hover { background: var(--primary); color: white; border-color: var(--primary); transform: translateY(-2px); box-shadow: 0 4px 10px rgba(37, 99, 235, 0.2); }
        .btn-random { background: var(--warning); color: white; border-color: var(--warning); }
        .btn-random:hover { background: #d97706; border-color: #d97706; box-shadow: 0 4px 10px rgba(245, 158, 11, 0.3); }

        .control-panel { background: #f8fafc; padding: 25px; border-radius: 16px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; border: 1px solid var(--border); border-left: 6px solid var(--primary); }
        .form-group label { font-weight: 600; margin-bottom: 8px; color: var(--text-main); display: block; font-size: 14px; }
        input[type="number"], input[type="text"] { padding: 12px 15px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 15px; font-family: 'Poppins', sans-serif; transition: border-color 0.3s; background: white; text-align: center; }
        input[type="number"]:focus, input[type="text"]:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1); }
        
        table { width: 100%; border-collapse: separate; border-spacing: 0; margin-top: 10px; border-radius: 12px; overflow: hidden; border: 1px solid var(--border); }
        th, td { padding: 15px; text-align: center; border-bottom: 1px solid var(--border); }
        th { background-color: #f1f5f9; color: var(--secondary); font-weight: 600; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background-color: #f8fafc; }
        
        .btn { padding: 12px 24px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-family: 'Poppins', sans-serif; font-size: 15px; transition: all 0.3s ease; }
        .btn-add { background-color: var(--accent); color: white; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2); }
        .btn-add:hover { background-color: var(--accent-hover); transform: translateY(-2px); }
        .btn-remove { background-color: #fee2e2; color: var(--danger); padding: 8px 16px; font-size: 13px; font-weight: 600; border-radius: 6px; }
        .btn-remove:hover { background-color: var(--danger); color: white; }
        
        .btn-submit { background: linear-gradient(135deg, var(--primary), #4f46e5); color: white; width: 100%; margin-top: 30px; font-size: 18px; padding: 18px; border-radius: 12px; box-shadow: 0 10px 25px rgba(37, 99, 235, 0.3); text-transform: uppercase; letter-spacing: 1px; }
        .btn-submit:hover { background: linear-gradient(135deg, #1e40af, #3730a3); transform: translateY(-3px); box-shadow: 0 15px 30px rgba(37, 99, 235, 0.4); }
        
        .error-box { display: none; background-color: #fef2f2; color: var(--danger); padding: 16px 20px; border-left: 5px solid var(--danger); margin-bottom: 25px; font-weight: 500; border-radius: 8px; font-size: 14px; }
    </style>
</head>
<body>

<div class="container">
    <h1>C5 <span>Simulator Pro</span></h1>
    
    <div class="presets">
        <strong>Presets:</strong>
        <button type="button" class="preset-btn" onclick="loadScenario('A')">Mixed (A)</button>
        <button type="button" class="preset-btn" onclick="loadScenario('B')">Short-Heavy (B)</button>
        <button type="button" class="preset-btn" onclick="loadScenario('C')">Fairness (C)</button>
        <button type="button" class="preset-btn" onclick="loadScenario('D')">Long-Job (D)</button>
        <button type="button" class="preset-btn" onclick="loadScenario('E')" style="color:var(--danger); border-color:var(--danger);">Invalid (E)</button>
        <button type="button" class="preset-btn btn-random" onclick="generateRandom()">🎲 Random</button>
        <button type="button" class="preset-btn" onclick="loadDefault()" style="background: #e2e8f0; border:none;">Reset</button>
    </div>

    <div class="error-box" id="errorBox"></div>

    <form id="simulatorForm" action="simulator.php" method="POST">
        <div class="control-panel">
            <div class="form-group">
                <label for="quantum">Time Quantum (RR)</label>
                <input type="number" id="quantum" name="quantum" min="1" required style="width: 150px;">
            </div>
            <button type="button" class="btn btn-add" onclick="addProcess('', '')">+ Add Process</button>
        </div>

        <table id="processTable">
            <thead>
                <tr>
                    <th>Process ID</th>
                    <th>Arrival Time</th>
                    <th>Burst Time</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="processBody"></tbody>
        </table>
        <button type="submit" class="btn btn-submit">Run Simulation & Auto-Analyze</button>
    </form>
</div>

<script>
    function clearTable() { document.getElementById('processBody').innerHTML = ''; }
    
    function addProcess(at = '', bt = '') {
        const tbody = document.getElementById('processBody');
        const count = tbody.children.length + 1;
        const row = document.createElement('tr');
        row.innerHTML = '<td><input type="text" name="pid[]" value="P' + count + '" required readonly style="background:#f8fafc; border:none; font-weight:600; color:#475569;"></td>' +
                        '<td><input type="number" name="arrival[]" value="' + at + '" required placeholder="0"></td>' +
                        '<td><input type="number" name="burst[]" value="' + bt + '" required placeholder="1"></td>' +
                        '<td><button type="button" class="btn btn-remove" onclick="this.closest(\'tr\').remove(); updateIDs();">Remove</button></td>';
        tbody.appendChild(row);
    }
    
    function updateIDs() {
        const rows = document.querySelectorAll('#processBody tr');
        rows.forEach(function(row, index) { row.querySelector('input[name="pid[]"]').value = "P" + (index + 1); });
    }
    
    function loadDefault() { clearTable(); document.getElementById('quantum').value = 2; addProcess(0, 1); document.getElementById('errorBox').style.display = 'none'; }
    
    function loadScenario(type) {
        clearTable(); document.getElementById('errorBox').style.display = 'none';
        if (type === 'A') { document.getElementById('quantum').value = 3; addProcess(0, 5); addProcess(1, 3); addProcess(2, 8); addProcess(3, 6); }
        else if (type === 'B') { document.getElementById('quantum').value = 2; addProcess(0, 1); addProcess(0, 2); addProcess(0, 10); addProcess(0, 3); }
        else if (type === 'C') { document.getElementById('quantum').value = 2; addProcess(0, 8); addProcess(0, 8); addProcess(0, 8); }
        else if (type === 'D') { document.getElementById('quantum').value = 4; addProcess(0, 15); addProcess(2, 2); addProcess(3, 3); }
        else if (type === 'E') { document.getElementById('quantum').value = -1; addProcess(-2, 0); addProcess(0, -5); }
    }

    function generateRandom() {
        clearTable(); document.getElementById('errorBox').style.display = 'none';
        document.getElementById('quantum').value = Math.floor(Math.random() * 4) + 2; // Random quantum 2-5
        const numProcesses = Math.floor(Math.random() * 4) + 4; // 4 to 7 processes
        for(let i=0; i<numProcesses; i++) {
            let at = Math.floor(Math.random() * 6); // Arrival 0-5
            let bt = Math.floor(Math.random() * 10) + 1; // Burst 1-10
            addProcess(at, bt);
        }
    }
    
    window.onload = function() { loadDefault(); };

    document.getElementById('simulatorForm').addEventListener('submit', function(e) {
        const errorBox = document.getElementById('errorBox');
        errorBox.style.display = 'none'; errorBox.innerHTML = '';
        let errors = [];
        
        const quantumVal = parseInt(document.getElementById('quantum').value);
        if (isNaN(quantumVal) || quantumVal <= 0) errors.push("Quantum must be greater than 0.");
        
        const arrivals = document.querySelectorAll('input[name="arrival[]"]');
        const bursts = document.querySelectorAll('input[name="burst[]"]');
        
        if (arrivals.length === 0) errors.push("You must add at least one process.");
        
        for (let i = 0; i < arrivals.length; i++) {
            if (parseInt(arrivals[i].value) < 0) errors.push("P" + (i+1) + ": Arrival Time cannot be negative.");
            if (parseInt(bursts[i].value) <= 0) errors.push("P" + (i+1) + ": Burst Time must be greater than 0.");
        }
        
        if (errors.length > 0) { e.preventDefault(); errorBox.innerHTML = errors.join('<br>'); errorBox.style.display = 'block'; }
    });
</script>
</body>
</html>
