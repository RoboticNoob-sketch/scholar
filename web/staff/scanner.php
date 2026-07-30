<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';

$user = require_role($pdo, ['staff']);
$batchId = (int) ($_GET['batch_id'] ?? 0);

$batches = $pdo->query('SELECT id, name FROM distribution_batches WHERE status="open" ORDER BY distribution_date DESC')->fetchAll();
if (!$batchId && $batches) {
    $batchId = (int) $batches[0]['id'];
}

$batch = null;
if ($batchId) {
    $stmt = $pdo->prepare('SELECT b.*, p.name AS program_name FROM distribution_batches b JOIN scholarship_programs p ON p.id = b.program_id WHERE b.id = ? AND b.status="open"');
    $stmt->execute([$batchId]);
    $batch = $stmt->fetch();
}

render_staff_layout($pdo, 'scanner', 'QR Scanner', function () use ($batch, $batches, $batchId): void {
    echo '<div class="page-header"><h1 class="page-title">QR Scanner</h1></div>';
    if (!$batch) {
        echo '<div class="empty-state">Select an open batch from the <a href="' . base_url('staff/dashboard.php') . '">dashboard</a>.</div>';
        return;
    }

    echo '<div class="scanner-layout">';
    echo '<div class="card"><div class="card-title"><i data-lucide="scan-line"></i> ' . e($batch['name']) . '</div>';
    echo '<p class="page-subtitle">' . e($batch['program_name']) . '</p></div>';

    echo '<div class="scanner-box">';
    echo '<div id="verifiedBanner" class="scan-result success" hidden style="margin-bottom:12px"><h3>Profile verified</h3><p id="verifiedName"></p><p style="font-size:12px;opacity:.85">Scan voucher within 5 minutes</p></div>';
    echo '<div class="mode-toggle" id="modeToggle">';
    echo '<button type="button" data-mode="profile">PROFILE VERIFY</button>';
    echo '<button type="button" class="active" data-mode="voucher">VOUCHER SCAN</button>';
    echo '</div>';
    echo '<p class="page-subtitle" style="margin:8px 0 12px">Step 1: verify scholar profile · Step 2: scan voucher</p>';
    echo '<div id="reader"></div>';
    echo '<div id="scanResult" class="scan-result" hidden></div>';
    echo '<form id="manualForm" class="form-row manual-form">';
    echo '<input class="input" id="manualCode" placeholder="Enter voucher code manually">';
    echo '<button class="btn btn-secondary btn-sm" type="submit">SUBMIT</button>';
    echo '</form>';
    echo '</div>';

    echo '<div class="card"><div class="card-title"><i data-lucide="history"></i> Recent claims at this station</div><div id="recentClaims">Loading…</div></div>';
    echo '</div>';

    echo '<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>';
    echo '<script>';
    echo 'const batchId = ' . (int) $batchId . ';';
    echo 'let scanMode = "voucher";';
    echo 'let verificationToken = null;';
    echo 'let verifiedScholarName = null;';
    echo 'const resultEl = document.getElementById("scanResult");';
    echo 'const verifiedBanner = document.getElementById("verifiedBanner");';
    echo 'function showVerified(name){ verifiedScholarName=name; verifiedBanner.hidden=false; document.getElementById("verifiedName").textContent=name; }';
    echo 'function clearVerified(){ verificationToken=null; verifiedScholarName=null; verifiedBanner.hidden=true; }';
    echo 'function showResult(ok, title, lines){ resultEl.hidden=false; resultEl.className="scan-result "+(ok?"success":"error"); resultEl.innerHTML="<h3>"+title+"</h3>"+lines.map(l=>"<p>"+l+"</p>").join(""); }';
    echo 'async function redeem(payload){ const body={batch_id:batchId,payload}; if(verificationToken) body.verification_token=verificationToken; const r=await fetch("' . base_url('api/claims/redeem.php') . '",{method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify(body)}); return r.json(); }';
    echo 'async function verify(payload){ const r=await fetch("' . base_url('api/claims/verify-profile.php') . '",{method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify({payload,batch_id:batchId})}); return r.json(); }';
    echo 'async function handlePayload(payload){ if(scanMode==="profile"){ const data=await verify(payload); if(data.success && data.verification_token){ verificationToken=data.verification_token; showVerified(data.scholar?data.scholar.name:"Scholar verified"); scanMode="voucher"; document.querySelectorAll("#modeToggle button").forEach(b=>b.classList.toggle("active",b.dataset.mode==="voucher")); } else { clearVerified(); } showResult(data.success,data.message||data.error,[data.scholar?data.scholar.name+" · "+data.scholar.student_no:""].filter(Boolean)); return; } if(!verificationToken){ showResult(false,"Profile verification required",["Scan the scholar profile QR first, then scan the voucher."]); return; } const data=await redeem(payload); if(data.success){ clearVerified(); } showResult(data.success,data.message||data.error,[data.scholar?data.scholar.name+" · "+data.scholar.student_no:"", data.amount?"Amount: "+data.amount:""].filter(Boolean)); if(data.success) loadRecent(); }';
    echo 'document.getElementById("modeToggle").addEventListener("click",e=>{ if(e.target.dataset.mode){ scanMode=e.target.dataset.mode; document.querySelectorAll("#modeToggle button").forEach(b=>b.classList.toggle("active",b.dataset.mode===scanMode)); }});';
    echo 'document.getElementById("manualForm").addEventListener("submit",e=>{ e.preventDefault(); const v=document.getElementById("manualCode").value.trim(); if(v) handlePayload(v.startsWith("VCH|")?v:"VCH|"+v); });';
    echo 'async function loadRecent(){ const r=await fetch("' . base_url('api/claims/recent.php') . '?batch_id="+batchId); const data=await r.json(); document.getElementById("recentClaims").innerHTML=(data.items||[]).map(i=>"<div class=\\"activity-item\\"><div class=\\"activity-top\\"><span class=\\"activity-who\\">"+i.name+"</span><span class=\\"badge badge-accent\\">claimed</span></div><div class=\\"activity-meta\\">"+i.time+"</div></div>").join("")||"<div class=\\"empty-state\\">No claims yet.</div>"; }';
    echo 'loadRecent();';
    echo 'const scanner=new Html5Qrcode("reader"); Html5Qrcode.getCameras().then(cams=>{ const id=cams.length?cams[0].id:null; if(!id){ document.getElementById("reader").innerHTML="<div class=\\"empty-state\\">No camera found. Use manual entry.</div>"; return; } scanner.start(id,{fps:10,qrbox:250},text=>handlePayload(text)); });';
    echo '</script>';
});
