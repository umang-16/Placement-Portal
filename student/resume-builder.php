<?php
session_start();
require_once __DIR__ . "/../db.php";

/* 🔐 LOGIN CHECK */
if(!isset($_SESSION['student_id'])){ 
    header("Location: ../login-selection.php"); 
    exit(); 
}

$student_id = (int)$_SESSION['student_id'];
$student_res = mysqli_query($conn, "SELECT name, email, contact, department, skills, bio FROM students WHERE id = $student_id");
$student = mysqli_fetch_assoc($student_res);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Dynamic Pro Resume Builder - Placement Portal</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<style>
    /* ✨ MAIN DASHBOARD THEME ✨ */
    *{margin:0;padding:0;box-sizing:border-box;font-family:"Segoe UI",sans-serif}
    body { background:#0a0f1a; color: #f8fafc; height: 100vh; display: flex; flex-direction: column; overflow: hidden; }

      /* 🔵 STUDENT NAVBAR CSS */
     .navbar { background: rgba(10, 15, 26, 0.85) !important; backdrop-filter: blur(10px) !important; padding: 15px 35px !important; display: flex !important; justify-content: space-between !important; align-items: center !important; box-shadow: 0 4px 30px rgba(0,0,0,0.5) !important; position: sticky !important; top: 0 !important; z-index: 1000 !important; border-bottom: 1px solid rgba(255,255,255,0.05) !important;}
    .nav-logo-container { display: flex !important; align-items: center !important; text-decoration: none !important; }
  
    /* 👇 અહી મેં લોગોની સાઈઝ 2.2 ગણી કરી છે 👇 */
    .nav-logo-img { height: 45px !important; width: auto !important; transform: scale(2.2); transform-origin: left center; transition: transform 0.3s ease !important; }
    .nav-logo-container:hover .nav-logo-img { transform: scale(2.3) !important; }
    .back-btn { color: #cbd5e1; text-decoration: none; font-weight: bold; font-size: 14px; transition: 0.3s;}
    .back-btn:hover { color: #38bdf8; }

    .builder-layout { display: flex; flex: 1; overflow: hidden; }

    /* 📝 LEFT SIDE: FORM PANEL */
    .form-panel { flex: 1; padding: 30px; overflow-y: auto; background: rgba(255,255,255,0.02); border-right: 1px solid rgba(255,255,255,0.05); }
    .form-panel::-webkit-scrollbar { width: 8px; }
    .form-panel::-webkit-scrollbar-thumb { background: #38bdf8; border-radius: 10px; }
    
    h2 { color: #f8fafc; font-size: 22px; margin-bottom: 5px; font-weight: 800;}
    .form-section { background: rgba(0,0,0,0.2); padding: 20px; border-radius: 12px; margin-bottom: 20px; border: 1px solid rgba(255,255,255,0.05); position: relative;}
    .form-section h3 { font-size: 13px; color: #38bdf8; text-transform: uppercase; margin-bottom: 15px; font-weight: 800; border-bottom: 1px dashed rgba(255,255,255,0.1); padding-bottom: 5px;}
    
    .dynamic-block { background: rgba(255,255,255,0.02); border: 1px dashed rgba(255,255,255,0.1); padding: 15px; border-radius: 8px; margin-bottom: 15px; position: relative;}
    .remove-btn { position: absolute; top: 10px; right: 10px; background: rgba(239,68,68,0.1); color: #ef4444; border: none; padding: 4px 8px; border-radius: 4px; font-size: 11px; cursor: pointer; font-weight: bold; transition: 0.2s;}
    .remove-btn:hover { background: #ef4444; color: #fff;}

    .add-more-btn { background: rgba(56, 189, 248, 0.1); color: #38bdf8; border: 1px dashed #38bdf8; padding: 8px 15px; border-radius: 6px; cursor: pointer; font-size: 12px; font-weight: bold; width: 100%; transition: 0.3s;}
    .add-more-btn:hover { background: #38bdf8; color: #0a0f1a;}

    label { font-weight:800; display:block; margin-top:12px; color: #94a3b8; font-size: 11px; text-transform: uppercase;}
    input, textarea { width:100%; padding:12px; margin-top:5px; border:1px solid rgba(255,255,255,0.1); border-radius:6px; background: rgba(0,0,0,0.4); color: #fff; font-size: 13px; transition: 0.3s; font-family: inherit; }
    input:focus, textarea:focus { outline: none; border-color: #38bdf8; box-shadow: 0 0 10px rgba(56, 189, 248, 0.2); }
    textarea { resize: vertical; min-height: 80px; }
    
    .btn-download { width:100%; padding:15px; background: #f8fafc; color:#0f172a; border:none; margin-top:10px; margin-bottom: 40px; cursor:pointer; font-size:16px; border-radius:8px; font-weight:900; transition: 0.3s; box-shadow: 0 4px 15px rgba(255, 255, 255, 0.2); text-transform: uppercase; letter-spacing: 1px;}
    .btn-download:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(255, 255, 255, 0.4); background: #38bdf8; color: #fff;}

    /* 📄 RIGHT SIDE: LIVE PREVIEW (A4 PAPER - BLACK & WHITE MINIMALIST) */
    .preview-panel { flex: 1.2; background: #0f172a; padding: 40px; display: flex; justify-content: center; overflow-y: auto; }
    .preview-panel::-webkit-scrollbar { width: 8px; }
    .preview-panel::-webkit-scrollbar-thumb { background: #475569; border-radius: 10px; }

    .a4-paper { 
        background: #ffffff; color: #000000;
        width: 210mm; min-height: 297mm; 
        box-shadow: 0 20px 50px rgba(0,0,0,0.5); 
        font-family: 'Helvetica', 'Arial', sans-serif; 
        transform: scale(0.85); transform-origin: top center;
        padding: 50px 40px;
    }

    /* 🖤 B&W MINIMALIST STYLES */
    .res-header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 15px; margin-bottom: 20px; }
    .res-name { font-size: 32px; font-weight: 900; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 5px; }
    .res-role { font-size: 14px; text-transform: uppercase; letter-spacing: 2px; color: #555; margin-bottom: 12px; }
    .res-contacts { font-size: 11px; color: #333; display: flex; justify-content: center; gap: 15px; flex-wrap: wrap; }
    
    .res-section-title { font-size: 13px; font-weight: 900; text-transform: uppercase; letter-spacing: 1px; border-bottom: 1px solid #000; margin: 20px 0 12px 0; padding-bottom: 4px; color: #000; }
    
    /* MULTILINE MAGIC HERE */
    .res-text-content { font-size: 12px; line-height: 1.6; color: #222; text-align: justify; white-space: pre-wrap; word-wrap: break-word; }
    
    .res-item { margin-bottom: 15px; }
    .res-item-top { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 3px; }
    .res-item-title { font-size: 13px; font-weight: bold; color: #000; }
    .res-item-date { font-size: 11px; font-weight: bold; color: #000; }
    .res-item-sub { display: flex; justify-content: space-between; font-size: 12px; color: #555; font-style: italic; margin-bottom: 8px; }
</style>
</head>
<body>

<nav class="navbar">
    <img src="../assets/logo.png" alt="Logo" class="nav-logo-img" onerror="this.src='../assets/logo.jpg'">
    <a href="profile.php" class="back-btn"><i class="fa fa-arrow-left"></i> Back to Profile</a>
</nav>

<div class="builder-layout">
    <div class="form-panel" id="form-container">
        <h2>Executive Resume Builder</h2>
        <p style="color: #94a3b8; font-size: 12px; margin-bottom: 20px;">Black & White Minimalist ATS-Friendly Template.</p>

        <div class="form-section">
            <h3>Identity & Contact</h3>
            <label>Full Name</label>
            <input type="text" id="inp_name" value="<?= htmlspecialchars($student['name'] ?? '') ?>">
            <label>Professional Role</label>
            <input type="text" id="inp_role" value="<?= htmlspecialchars($student['department'] ?? '') ?> Student">
            <div style="display:flex; gap:10px;">
                <div style="flex:1;"><label>Email</label><input type="text" id="inp_email" value="<?= htmlspecialchars($student['email'] ?? '') ?>"></div>
                <div style="flex:1;"><label>Phone</label><input type="text" id="inp_phone" value="<?= htmlspecialchars($student['contact'] ?? '') ?>"></div>
            </div>
            <div style="display:flex; gap:10px;">
                <div style="flex:1;"><label>LinkedIn URL</label><input type="text" id="inp_link" placeholder="linkedin.com/in/username"></div>
                <div style="flex:1;"><label>Location</label><input type="text" id="inp_location" placeholder="City, State"></div>
            </div>
        </div>

        <div class="form-section">
            <h3>Professional Summary</h3>
            <textarea id="inp_bio" placeholder="Write a short objective... (Press Enter for new line)"><?= htmlspecialchars($student['bio'] ?? '') ?></textarea>
        </div>

        <div class="form-section" id="exp_wrapper">
            <h3>Experience / Internships</h3>
            <div id="exp_list">
                <div class="dynamic-block exp-block">
                    <label>Job / Intern Title</label><input type="text" class="e-title" placeholder="e.g. Web Developer Intern">
                    <label>Company Name</label><input type="text" class="e-comp" placeholder="e.g. Tech Solutions Pvt Ltd">
                    <label>Duration (Dates)</label><input type="text" class="e-date" placeholder="e.g. May 2024 - Aug 2024">
                    <label>Responsibilities & Details (Use Enter for new line)</label>
                    <textarea class="e-desc" placeholder="- Developed frontend using React&#10;- Improved website speed by 20%"></textarea>
                </div>
            </div>
            <button class="add-more-btn" onclick="addExperience()">+ Add Another Experience</button>
        </div>

        <div class="form-section" id="edu_wrapper">
            <h3>Education</h3>
            <div id="edu_list">
                <div class="dynamic-block edu-block">
                    <label>Degree / Course</label><input type="text" class="ed-deg" value="B.Tech in <?= htmlspecialchars($student['department'] ?? '') ?>">
                    <label>College / University</label><input type="text" class="ed-col" value="My Institute of Technology">
                    <div style="display:flex; gap:10px;">
                        <div style="flex:1;"><label>Passing Year</label><input type="text" class="ed-year" value="2026"></div>
                        <div style="flex:1;"><label>CGPA / Percentage</label><input type="text" class="ed-cgpa" placeholder="e.g. 8.5 CGPA"></div>
                    </div>
                </div>
            </div>
            <button class="add-more-btn" onclick="addEducation()">+ Add Another Education</button>
        </div>

        <div class="form-section" id="proj_wrapper">
            <h3>Projects</h3>
            <div id="proj_list">
                <div class="dynamic-block proj-block">
                    <label>Project Title</label><input type="text" class="p-title" placeholder="e.g. E-Commerce Platform">
                    <label>Technologies Used</label><input type="text" class="p-tech" placeholder="e.g. PHP, MySQL, Bootstrap">
                    <label>Project Description</label>
                    <textarea class="p-desc" placeholder="- Built a secure login system&#10;- Integrated payment gateway"></textarea>
                </div>
            </div>
            <button class="add-more-btn" onclick="addProject()">+ Add Another Project</button>
        </div>

        <div class="form-section">
            <h3>Technical Skills</h3>
            <textarea id="inp_skills" placeholder="e.g. Languages: C++, Python&#10;Web: HTML, CSS, React"><?= htmlspecialchars($student['skills'] ?? '') ?></textarea>
        </div>

        <div class="form-section">
            <h3>Achievements & Certifications</h3>
            <textarea id="inp_achievements" placeholder="- 1st Prize in Hackathon 2024&#10;- AWS Cloud Certified"></textarea>
        </div>

        <button id="downloadBtn" class="btn-download"><i class="fa fa-download"></i> Download & Save Resume</button>
    </div>

    <div class="preview-panel">
        <div id="resume-document" class="a4-paper">
            
            <div class="res-header">
                <div class="res-name" id="out_name">JOHN DOE</div>
                <div class="res-role" id="out_role">Software Engineer</div>
                <div class="res-contacts">
                    <span id="out_email">john@example.com</span> | 
                    <span id="out_phone">+91 0000000000</span> | 
                    <span id="out_link">linkedin.com/in/johndoe</span> | 
                    <span id="out_location">City, State</span>
                </div>
            </div>

            <div id="sec_bio">
                <div class="res-section-title">Professional Summary</div>
                <div class="res-text-content" id="out_bio">Summary text...</div>
            </div>

            <div id="sec_exp">
                <div class="res-section-title">Experience</div>
                <div id="out_exp_container"></div>
            </div>

            <div id="sec_edu">
                <div class="res-section-title">Education</div>
                <div id="out_edu_container"></div>
            </div>

            <div id="sec_proj">
                <div class="res-section-title">Projects</div>
                <div id="out_proj_container"></div>
            </div>

            <div id="sec_skills">
                <div class="res-section-title">Technical Skills</div>
                <div class="res-text-content" id="out_skills">Skills...</div>
            </div>

            <div id="sec_achievements">
                <div class="res-section-title">Achievements</div>
                <div class="res-text-content" id="out_achievements">Achievements...</div>
            </div>

        </div>
    </div>
</div>

<script>
// ➕ ADD MORE FUNCTIONS
function addExperience() {
    const list = document.getElementById('exp_list');
    const div = document.createElement('div');
    div.className = 'dynamic-block exp-block';
    div.innerHTML = `
        <button class="remove-btn" onclick="this.parentElement.remove(); updatePreview();">&times; Remove</button>
        <label>Job / Intern Title</label><input type="text" class="e-title" placeholder="e.g. Frontend Developer">
        <label>Company Name</label><input type="text" class="e-comp" placeholder="e.g. ABC Corp">
        <label>Duration (Dates)</label><input type="text" class="e-date" placeholder="e.g. Jan 2023 - Present">
        <label>Responsibilities & Details</label><textarea class="e-desc" placeholder="- Worked on UI updates..."></textarea>
    `;
    list.appendChild(div);
}

function addEducation() {
    const list = document.getElementById('edu_list');
    const div = document.createElement('div');
    div.className = 'dynamic-block edu-block';
    div.innerHTML = `
        <button class="remove-btn" onclick="this.parentElement.remove(); updatePreview();">&times; Remove</button>
        <label>Degree / Course</label><input type="text" class="ed-deg" placeholder="e.g. Class 12th">
        <label>College / School</label><input type="text" class="ed-col" placeholder="e.g. XYZ School">
        <div style="display:flex; gap:10px;">
            <div style="flex:1;"><label>Passing Year</label><input type="text" class="ed-year" placeholder="e.g. 2022"></div>
            <div style="flex:1;"><label>CGPA / Percentage</label><input type="text" class="ed-cgpa" placeholder="e.g. 90%"></div>
        </div>
    `;
    list.appendChild(div);
}

function addProject() {
    const list = document.getElementById('proj_list');
    const div = document.createElement('div');
    div.className = 'dynamic-block proj-block';
    div.innerHTML = `
        <button class="remove-btn" onclick="this.parentElement.remove(); updatePreview();">&times; Remove</button>
        <label>Project Title</label><input type="text" class="p-title" placeholder="e.g. Chat App">
        <label>Technologies Used</label><input type="text" class="p-tech" placeholder="e.g. Node.js, Socket.io">
        <label>Project Description</label><textarea class="p-desc" placeholder="- Real time messaging..."></textarea>
    `;
    list.appendChild(div);
}

// ⚡ LIVE PREVIEW UPDATE LOGIC
function updatePreview() {
    // 1. Static Text Mapping
    const staticMap = ['name', 'role', 'email', 'phone', 'link', 'location', 'bio', 'skills', 'achievements'];
    staticMap.forEach(id => {
        let inp = document.getElementById('inp_' + id);
        let out = document.getElementById('out_' + id);
        if(inp && out) out.innerText = inp.value;
    });

    // 2. Hide static sections if empty
    document.getElementById('sec_bio').style.display = document.getElementById('inp_bio').value.trim() ? 'block' : 'none';
    document.getElementById('sec_skills').style.display = document.getElementById('inp_skills').value.trim() ? 'block' : 'none';
    document.getElementById('sec_achievements').style.display = document.getElementById('inp_achievements').value.trim() ? 'block' : 'none';
    
    let loc = document.getElementById('inp_location').value.trim();
    document.getElementById('out_location').innerText = loc;

    // 3. Dynamic Experience
    let expHtml = '';
    document.querySelectorAll('.exp-block').forEach(block => {
        let t = block.querySelector('.e-title').value.trim();
        let c = block.querySelector('.e-comp').value.trim();
        let d = block.querySelector('.e-date').value.trim();
        let desc = block.querySelector('.e-desc').value.trim();
        if(t || c || desc) {
            expHtml += `
            <div class="res-item">
                <div class="res-item-top"><div class="res-item-title">${t}</div><div class="res-item-date">${d}</div></div>
                <div class="res-item-sub"><span>${c}</span></div>
                <div class="res-text-content">${desc}</div>
            </div>`;
        }
    });
    document.getElementById('out_exp_container').innerHTML = expHtml;
    document.getElementById('sec_exp').style.display = expHtml ? 'block' : 'none';

    // 4. Dynamic Education
    let eduHtml = '';
    document.querySelectorAll('.edu-block').forEach(block => {
        let deg = block.querySelector('.ed-deg').value.trim();
        let col = block.querySelector('.ed-col').value.trim();
        let yr = block.querySelector('.ed-year').value.trim();
        let cgpa = block.querySelector('.ed-cgpa').value.trim();
        if(deg || col) {
            let marks = cgpa ? ` | ${cgpa}` : "";
            eduHtml += `
            <div class="res-item">
                <div class="res-item-top"><div class="res-item-title">${deg}</div><div class="res-item-date">${yr}</div></div>
                <div class="res-item-sub"><span>${col} ${marks}</span></div>
            </div>`;
        }
    });
    document.getElementById('out_edu_container').innerHTML = eduHtml;
    document.getElementById('sec_edu').style.display = eduHtml ? 'block' : 'none';

    // 5. Dynamic Projects
    let projHtml = '';
    document.querySelectorAll('.proj-block').forEach(block => {
        let pt = block.querySelector('.p-title').value.trim();
        let ptech = block.querySelector('.p-tech').value.trim();
        let pdesc = block.querySelector('.p-desc').value.trim();
        if(pt || pdesc) {
            projHtml += `
            <div class="res-item">
                <div class="res-item-top"><div class="res-item-title">${pt}</div><div class="res-item-date" style="font-weight:normal; font-style:italic;">${ptech}</div></div>
                <div class="res-text-content" style="margin-top:4px;">${pdesc}</div>
            </div>`;
        }
    });
    document.getElementById('out_proj_container').innerHTML = projHtml;
    document.getElementById('sec_proj').style.display = projHtml ? 'block' : 'none';
}

// 🟢 EVENT DELEGATION (Auto-update when typing anywhere)
document.getElementById('form-container').addEventListener('input', function(e) {
    if(e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
        updatePreview();
    }
});

// Run once on load
updatePreview();

// 🖨️ PDF GENERATION & AUTO-REDIRECT
document.getElementById('downloadBtn').addEventListener('click', () => {
    const btn = document.getElementById('downloadBtn');
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Generating...';
    
    const element = document.getElementById('resume-document');
    const opt = {
        margin:       0,
        filename:     (document.getElementById('inp_name').value || 'Student') + '_Resume.pdf',
        image:        { type: 'jpeg', quality: 1 },
        html2canvas:  { scale: 2 }, 
        jsPDF:        { unit: 'in', format: 'a4', orientation: 'portrait' }
    };
    
    element.style.transform = 'scale(1)';
    html2pdf().set(opt).from(element).save().then(() => {
        element.style.transform = 'scale(0.85)'; 
        btn.innerHTML = '<i class="fa fa-check-circle"></i> Downloaded! Redirecting...';
        btn.style.background = "#10b981"; // Green success color
        btn.style.color = "#fff";
        
        // ✨ REDIRECT TO PROFILE AFTER 1.5 SECONDS ✨
        setTimeout(() => {
            window.location.href = 'profile.php';
        }, 1500);
    });
});
</script>
</body>
</html>
