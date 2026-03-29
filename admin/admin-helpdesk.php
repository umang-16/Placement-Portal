<?php
$lifetime = 60 * 60 * 24 * 30; 
ini_set('session.gc_maxlifetime', $lifetime);
session_set_cookie_params($lifetime);
session_start();
require_once __DIR__ . "/../db.php"; 

if(!isset($_SESSION['admin_id'])){
    header("Location: admin-login.php");
    exit();
}

$msg = "";

/* ✉️ ADMIN REPLY LOGIC */
if(isset($_POST['reply_ticket'])){
    $ticket_id = (int)$_POST['ticket_id'];
    $sender_type = $_POST['sender_type'];
    $sender_id = (int)$_POST['sender_id'];
    $reply_msg = mysqli_real_escape_string($conn, $_POST['reply_message']);
    
    // 1. Update ticket status to resolved
    mysqli_query($conn, "UPDATE helpdesk_tickets SET status='resolved' WHERE id=$ticket_id");
    
    // 2. Send notification to user
    $notif_text = "🎧 Support Reply: " . $reply_msg;
    
    if($sender_type == 'student'){
        mysqli_query($conn, "INSERT INTO notifications (student_id, message, is_read, created_at) VALUES ($sender_id, '$notif_text', 0, NOW())");
    } else {
        mysqli_query($conn, "INSERT INTO notifications (company_id, student_id, message, is_read, created_at) VALUES ($sender_id, 0, '$notif_text', 0, NOW())");
    }
    
    $msg = "<div class='msg-box success'><i class='fa fa-circle-check'></i> Reply sent! Ticket marked as resolved.</div>";
}

/* 📊 FETCH TICKETS */
$sql = "SELECT t.*, 
               CASE WHEN t.sender_type = 'student' THEN s.name ELSE c.company_name END AS sender_name,
               CASE WHEN t.sender_type = 'student' THEN s.email ELSE c.email END AS sender_email
        FROM helpdesk_tickets t
        LEFT JOIN students s ON t.sender_type = 'student' AND t.sender_id = s.id
        LEFT JOIN companies c ON t.sender_type = 'company' AND t.sender_id = c.id
        ORDER BY t.status ASC, t.created_at DESC";
$tickets = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Helpdesk - Placement Portal</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
/* ✨ DARK & GLASS THEME ✨ */
*{margin:0;padding:0;box-sizing:border-box;font-family:"Segoe UI",sans-serif}
body{background:#0a0f1a; color: #f8fafc;}
main{padding:40px; max-width:1000px; margin:auto}
h1{color: #f8fafc; font-size: 26px; font-weight: 800; margin-bottom: 20px;}

.ticket-card { background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(15px); padding: 25px; border-radius: 16px; border: 1px solid rgba(255, 255, 255, 0.05); margin-bottom: 20px; position: relative; transition: 0.3s; box-shadow: 0 15px 35px rgba(0,0,0,0.2);}
.ticket-card:hover { background: rgba(255, 255, 255, 0.04); transform: translateX(5px); }
.border-student { border-left: 5px solid #38bdf8; }
.border-company { border-left: 5px solid #f59e0b; }

.badge { padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: bold; text-transform: uppercase; border: 1px solid transparent; margin-bottom: 10px; display: inline-block;}
.badge-student { background: rgba(56, 189, 248, 0.1); color: #38bdf8; border-color: rgba(56, 189, 248, 0.3); }
.badge-company { background: rgba(245, 158, 11, 0.1); color: #f59e0b; border-color: rgba(245, 158, 11, 0.3); }
.badge-status-pending { background: rgba(239, 68, 68, 0.1); color: #f87171; border-color: rgba(239, 68, 68, 0.3); float: right;}
.badge-status-resolved { background: rgba(16, 185, 129, 0.1); color: #34d399; border-color: rgba(16, 185, 129, 0.3); float: right;}

.ticket-title { font-size: 18px; color: #f8fafc; font-weight: 800; margin-bottom: 5px;}
.ticket-sender { font-size: 13px; color: #94a3b8; font-weight: 600; margin-bottom: 15px;}
.ticket-msg { font-size: 14px; color: #cbd5e1; line-height: 1.6; background: rgba(0,0,0,0.2); padding: 15px; border-radius: 8px; border: 1px dashed rgba(255,255,255,0.1); margin-bottom: 15px;}

.btn-reply { background: #38bdf8; color: #0a0f1a; padding: 10px 15px; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; transition: 0.3s; font-size: 13px;}
.btn-reply:hover { background: #0ea5e9; transform: translateY(-2px); box-shadow: 0 4px 10px rgba(56,189,248,0.3);}

/* MODAL CSS */
.modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); backdrop-filter: blur(8px); justify-content: center; align-items: center; z-index: 2000; }
.modal-content { background: #0f172a; padding: 35px; border-radius: 16px; width: 500px; position: relative; box-shadow: 0 20px 50px rgba(0,0,0,0.5); border: 1px solid rgba(255,255,255,0.1); }
.close-btn { position: absolute; top: 15px; right: 20px; font-size: 24px; cursor: pointer; color: #ef4444; background: rgba(239, 68, 68, 0.1); width: 35px; height: 35px; display: flex; justify-content: center; align-items: center; border-radius: 50%; transition: 0.3s; }
.close-btn:hover { background: #ef4444; color: #fff; transform: scale(1.1); }

.modal-content label { display: block; margin-top: 15px; font-weight: 800; font-size: 12px; color: #cbd5e1; text-transform: uppercase; letter-spacing: 1px; }
.form-input { width: 100%; padding: 14px; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); color: #fff; border-radius: 8px; margin-top: 8px; font-family: inherit; font-size: 14px; outline: none; transition: 0.3s; }
.form-input:focus { border-color: #38bdf8; box-shadow: 0 0 15px rgba(56, 189, 248, 0.2); }
.btn-submit { width: 100%; background: #10b981; color: #fff; padding: 15px; border: none; border-radius: 8px; margin-top: 25px; font-weight: 800; font-size: 16px; cursor: pointer; transition: 0.3s; }
.btn-submit:hover { background: #059669; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(16, 185, 129, 0.3); }

.msg-box{padding:15px;border-radius:8px;margin-bottom:25px;font-weight:bold; font-size: 14px; display: flex; align-items: center; gap: 8px;}
.success{background: rgba(16, 185, 129, 0.1); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3);}
</style>
</head>
<body>

<?php include 'admin_header.php'; ?>

<main>
    <h1><i class="fa fa-headset" style="color: #38bdf8;"></i> Support Helpdesk</h1>
    <?= $msg ?>

    <?php if(mysqli_num_rows($tickets) > 0): ?>
        <?php while($row = mysqli_fetch_assoc($tickets)): 
            $isStudent = ($row['sender_type'] == 'student');
            $borderClass = $isStudent ? 'border-student' : 'border-company';
            $badgeClass = $isStudent ? 'badge-student' : 'badge-company';
        ?>
            <div class="ticket-card <?= $borderClass ?>">
                <span class="badge badge-status-<?= $row['status'] ?>">
                    <?php if($row['status'] == 'pending') echo "<i class='fa fa-clock'></i> Pending"; else echo "<i class='fa fa-check-double'></i> Resolved"; ?>
                </span>
                
                <span class="badge <?= $badgeClass ?>"><?= strtoupper($row['sender_type']) ?></span>
                
                <div class="ticket-title">📌 <?= htmlspecialchars($row['subject']) ?></div>
                <div class="ticket-sender"><i class="fa fa-user"></i> <?= htmlspecialchars($row['sender_name']) ?> (<?= htmlspecialchars($row['sender_email']) ?>) &nbsp; | &nbsp; <i class="fa fa-calendar"></i> <?= date('d M, h:i A', strtotime($row['created_at'])) ?></div>
                
                <div class="ticket-msg">"<?= nl2br(htmlspecialchars($row['message'])) ?>"</div>

                <?php if($row['status'] == 'pending'): ?>
                    <button class="btn-reply" onclick="openReplyModal(<?= $row['id'] ?>, '<?= $row['sender_type'] ?>', <?= $row['sender_id'] ?>, '<?= addslashes(htmlspecialchars($row['sender_name'])) ?>')">
                        <i class="fa fa-reply"></i> Reply & Resolve
                    </button>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div style="text-align:center; padding: 60px; background: rgba(0,0,0,0.2); border-radius: 16px; border: 1px dashed rgba(255,255,255,0.1);">
            <i class="fa fa-check-circle" style="font-size: 50px; color: #10b981; margin-bottom: 15px;"></i>
            <p style="color: #94a3b8; font-weight: bold; font-size: 18px;">All caught up! No pending queries.</p>
        </div>
    <?php endif; ?>
</main>

<div id="replyModal" class="modal">
    <div class="modal-content">
        <span onclick="closeReplyModal()" class="close-btn">&times;</span>
        <h2 style="color:#f8fafc; font-size: 20px; margin-bottom: 15px; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 10px;"><i class="fa fa-reply" style="color: #38bdf8;"></i> Send Reply</h2>
        
        <p style="color: #cbd5e1; font-size: 13px; margin-bottom: 15px;">Replying to: <b id="reply_name" style="color: #38bdf8;"></b></p>
        
        <form method="POST">
            <input type="hidden" name="ticket_id" id="t_id">
            <input type="hidden" name="sender_type" id="t_type">
            <input type="hidden" name="sender_id" id="t_sid">
            
            <label>Admin Resolution Message</label>
            <textarea name="reply_message" class="form-input" rows="5" required placeholder="Type your solution here... It will be sent to their notifications."></textarea>
            
            <button type="submit" name="reply_ticket" class="btn-submit"><i class="fa fa-paper-plane"></i> Send & Mark Resolved</button>
        </form>
    </div>
</div>

<script>
    function openReplyModal(t_id, t_type, t_sid, name) { 
        document.getElementById('t_id').value = t_id;
        document.getElementById('t_type').value = t_type;
        document.getElementById('t_sid').value = t_sid;
        document.getElementById('reply_name').innerText = name;
        document.getElementById('replyModal').style.display = 'flex'; 
    }
    function closeReplyModal() { document.getElementById('replyModal').style.display = 'none'; }
    window.onclick = function(event) { if (event.target == document.getElementById('replyModal')) closeReplyModal(); }
</script>

</body>
</html>