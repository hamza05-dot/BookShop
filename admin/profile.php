<?php
session_start();
require_once '../includes/db.php';
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php'); exit();
}

$activePage = 'profile';
$message = '';
$messageType = 'success';

// Fetch fresh admin data
$stmt = $pdo->prepare("SELECT * FROM utilisateur WHERE idUser = ?");
$stmt->execute([$_SESSION['idUser']]);
$admin = $stmt->fetch();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $field = $_POST['field'] ?? '';

    if ($field === 'name') {
        $nom    = trim($_POST['nomUser'] ?? '');
        $prenom = trim($_POST['prenomUser'] ?? '');
        if ($nom && $prenom) {
            $pdo->prepare("UPDATE utilisateur SET nomUser=?, prenomUser=? WHERE idUser=?")
                ->execute([$nom, $prenom, $_SESSION['idUser']]);
            $_SESSION['nomUser']    = $nom;
            $_SESSION['prenomUser'] = $prenom;
            $message = "Name updated successfully.";
        }
    }

    if ($field === 'email') {
        $email = trim($_POST['email'] ?? '');
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $check = $pdo->prepare("SELECT idUser FROM utilisateur WHERE email=? AND idUser!=?");
            $check->execute([$email, $_SESSION['idUser']]);
            if ($check->fetch()) {
                $message = "This email is already in use.";
                $messageType = 'error';
            } else {
                $pdo->prepare("UPDATE utilisateur SET email=? WHERE idUser=?")->execute([$email, $_SESSION['idUser']]);
                $message = "Email updated successfully.";
            }
        } else {
            $message = "Invalid email address.";
            $messageType = 'error';
        }
    }

    if ($field === 'password') {
        $current = $_POST['currentPassword'] ?? '';
        $new     = $_POST['newPassword'] ?? '';
        $confirm = $_POST['confirmPassword'] ?? '';
        if (!password_verify($current, $admin['password'])) {
            $message = "Current password is incorrect.";
            $messageType = 'error';
        } elseif (strlen($new) < 6) {
            $message = "New password must be at least 6 characters.";
            $messageType = 'error';
        } elseif ($new !== $confirm) {
            $message = "New passwords do not match.";
            $messageType = 'error';
        } else {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE utilisateur SET password=? WHERE idUser=?")->execute([$hash, $_SESSION['idUser']]);
            $message = "Password updated successfully.";
        }
    }

    if ($field === 'avatar' && isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $mime    = mime_content_type($_FILES['image']['tmp_name']);
        if (in_array($mime, $allowed)) {
            $ext      = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = 'admin_' . $_SESSION['idUser'] . '_' . time() . '.' . $ext;
            $dest     = '../uploads/users/' . $filename;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
                if (!empty($admin['image']) && file_exists('../uploads/users/' . $admin['image'])) {
                    unlink('../uploads/users/' . $admin['image']);
                }
                $pdo->prepare("UPDATE utilisateur SET image=? WHERE idUser=?")->execute([$filename, $_SESSION['idUser']]);
                $_SESSION['image'] = $filename;
                $message = "Profile photo updated.";
            }
        } else {
            $message = "Invalid file type. Use JPEG, PNG, WEBP, or GIF.";
            $messageType = 'error';
        }
    }

    // Re-fetch updated data
    $stmt->execute([$_SESSION['idUser']]);
    $admin = $stmt->fetch();
}

$memberSince = date('F j, Y', strtotime($admin['createdAt']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile — BookShop Admin</title>
    <link rel="stylesheet" href="../assests/css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* ── Page Layout ─────────────────────────── */
        .profile-page {
            max-width: 760px;
            margin: 0 auto;
            padding: 10px 0 40px;
        }

        /* ── Hero Card ───────────────────────────── */
        .profile-hero {
            background: linear-gradient(135deg, var(--brown-dark) 0%, var(--brown-mid) 60%, var(--brown-light) 100%);
            border-radius: 20px;
            padding: 36px 36px 28px;
            display: flex;
            align-items: center;
            gap: 28px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(59, 36, 21, 0.28);
            margin-bottom: 24px;
        }
        .profile-hero::before {
            content: '';
            position: absolute;
            top: -60px; right: -60px;
            width: 200px; height: 200px;
            border-radius: 50%;
            background: rgba(255,255,255,0.06);
        }
        .profile-hero::after {
            content: '';
            position: absolute;
            bottom: -40px; left: 30%;
            width: 140px; height: 140px;
            border-radius: 50%;
            background: rgba(201,168,76,0.08);
        }
        .hero-avatar-wrap {
            position: relative;
            flex-shrink: 0;
        }
        .hero-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid rgba(201, 168, 76, 0.6);
            box-shadow: 0 4px 20px rgba(0,0,0,0.25);
            display: block;
        }
        .hero-avatar-placeholder {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: rgba(201, 168, 76, 0.2);
            border: 4px solid rgba(201, 168, 76, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 38px;
            font-weight: 700;
            color: var(--gold);
            letter-spacing: 1px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }
        .change-photo-btn {
            position: absolute;
            bottom: 3px; right: 3px;
            background: var(--gold);
            border: none;
            border-radius: 50%;
            width: 30px; height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            transition: transform 0.15s, background 0.15s;
            color: var(--brown-dark);
            z-index: 2;
        }
        .change-photo-btn:hover { transform: scale(1.15); background: #e0b84a; }
        .hero-info { flex: 1; z-index: 1; }
        .hero-name {
            font-size: 24px;
            font-weight: 700;
            color: #fff;
            margin: 0 0 4px;
            text-shadow: 0 1px 4px rgba(0,0,0,0.15);
        }
        .hero-email {
            font-size: 13.5px;
            color: var(--brown-pale);
            margin: 0 0 12px;
        }
        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: rgba(201, 168, 76, 0.2);
            border: 1px solid rgba(201, 168, 76, 0.5);
            color: var(--gold);
            font-size: 11px;
            font-weight: 600;
            padding: 4px 12px;
            border-radius: 20px;
            backdrop-filter: blur(4px);
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .hero-meta {
            font-size: 12px;
            color: rgba(255,255,255,0.55);
            margin-top: 10px;
        }
        .hero-id { font-size: 11px; color: rgba(255,255,255,0.35); margin-top: 4px; }

        /* ── Section Cards ───────────────────────── */
        .profile-section {
            background: var(--bg-card);
            border: 1.5px solid var(--border);
            border-radius: 14px;
            overflow: hidden;
            margin-bottom: 16px;
            box-shadow: var(--shadow);
        }
        .ps-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 22px;
            background: rgba(245, 239, 230, 0.6);
            border-bottom: 1px solid var(--border);
        }
        .ps-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            font-weight: 600;
            color: var(--brown-dark);
        }
        .ps-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: linear-gradient(135deg, var(--brown-dark), var(--brown-mid));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
        }
        .btn-edit-section {
            background: none;
            border: 1.5px solid var(--brown-light);
            color: var(--brown-mid);
            font-size: 12px;
            font-weight: 600;
            padding: 5px 14px;
            border-radius: 20px;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            transition: background 0.15s, color 0.15s, border-color 0.15s;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .btn-edit-section:hover {
            background: var(--brown-mid);
            color: #fff;
            border-color: var(--brown-mid);
        }
        .ps-body { padding: 18px 22px; }
        .info-row { display: flex; gap: 20px; flex-wrap: wrap; }
        .info-field { flex: 1; min-width: 180px; }
        .info-field label {
            font-size: 11px;
            font-weight: 600;
            color: var(--brown-pale);
            text-transform: uppercase;
            letter-spacing: 0.6px;
            display: block;
            margin-bottom: 4px;
        }
        .info-field p {
            font-size: 14.5px;
            color: var(--brown-dark);
            font-weight: 500;
            margin: 0;
        }

        /* ── Modal ───────────────────────────────── */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(30, 15, 5, 0.55);
            z-index: 300;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(3px);
        }
        .modal-overlay.open { display: flex; }
        .modal {
            background: var(--bg-card);
            border-radius: 18px;
            padding: 32px;
            max-width: 460px;
            width: 90%;
            position: relative;
            box-shadow: 0 20px 60px rgba(59, 36, 21, 0.25);
            animation: modalIn 0.22s ease;
            border: 1px solid var(--border);
        }
        @keyframes modalIn {
            from { transform: translateY(16px) scale(0.97); opacity: 0; }
            to   { transform: translateY(0) scale(1); opacity: 1; }
        }
        .modal-close {
            position: absolute;
            top: 16px; right: 18px;
            background: rgba(139, 111, 71, 0.12);
            border: none;
            border-radius: 50%;
            width: 32px; height: 32px;
            font-size: 17px;
            cursor: pointer;
            color: var(--brown-mid);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.15s;
        }
        .modal-close:hover { background: rgba(139, 111, 71, 0.22); }
        .modal-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--brown-dark);
            margin: 0 0 22px;
        }
        .modal .form-group { margin-bottom: 16px; }
        .modal .form-group label {
            font-size: 12px;
            font-weight: 600;
            color: var(--brown-light);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: block;
            margin-bottom: 6px;
        }
        .modal .form-group input {
            width: 100%;
            padding: 10px 14px;
            border: 1.5px solid var(--border);
            border-radius: 10px;
            font-size: 14px;
            font-family: 'Poppins', sans-serif;
            color: var(--brown-dark);
            background: rgba(245, 239, 230, 0.5);
            transition: border-color 0.15s, background 0.15s, box-shadow 0.15s;
            box-sizing: border-box;
        }
        .modal .form-group input:focus {
            outline: none;
            border-color: var(--brown-light);
            background: var(--white);
            box-shadow: 0 0 0 3px rgba(139, 111, 71, 0.12);
        }
        .btn-save {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, var(--brown-dark), var(--brown-mid));
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            font-family: 'Poppins', sans-serif;
            cursor: pointer;
            margin-top: 6px;
            transition: opacity 0.15s, transform 0.12s;
        }
        .btn-save:hover { opacity: 0.88; transform: translateY(-1px); }
        .btn-save:active { transform: translateY(0); }

        /* Avatar upload */
        .avatar-drop-zone {
            border: 2px dashed var(--brown-pale);
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: border-color 0.15s, background 0.15s;
            background: rgba(245, 239, 230, 0.4);
            margin-bottom: 16px;
        }
        .avatar-drop-zone:hover { border-color: var(--brown-mid); background: rgba(196, 168, 130, 0.12); }
        .avatar-drop-zone input[type="file"] { display: none; }
        .avatar-drop-zone .drop-icon { font-size: 36px; margin-bottom: 8px; }
        .avatar-drop-zone p { font-size: 13px; color: var(--brown-light); margin: 0; }
        .avatar-preview-wrap { display: none; text-align: center; margin-bottom: 16px; }
        .avatar-preview-wrap img {
            width: 90px; height: 90px;
            border-radius: 50%; object-fit: cover;
            border: 3px solid var(--brown-pale);
        }

        /* Password strength */
        .strength-bar-wrap { height: 4px; background: #e8ddd0; border-radius: 4px; margin-top: 6px; overflow: hidden; }
        .strength-bar { height: 100%; border-radius: 4px; width: 0; transition: width 0.3s, background 0.3s; }

        /* Message */
        .profile-message {
            padding: 12px 18px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .profile-message.success { background: #F0F7F4; border: 1px solid rgba(44,74,62,0.18); color: var(--green-dark); }
        .profile-message.error   { background: #FDF2F2; border: 1px solid rgba(166,61,64,0.18); color: var(--red-soft); }
    </style>
</head>
<body>
<?php include '../includes/nav.php'; ?>

<div class="main">
    <div class="profile-page">

        <?php if ($message): ?>
            <div class="profile-message <?= $messageType ?>">
                <?= $messageType === 'success' ? '✓' : '⚠' ?> <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- ── Hero Card ── -->
        <div class="profile-hero">
            <div class="hero-avatar-wrap">
                <?php if (!empty($admin['image'])): ?>
                    <img class="hero-avatar" src="../uploads/users/<?= htmlspecialchars($admin['image']) ?>" alt="Avatar">
                <?php else: ?>
                    <div class="hero-avatar-placeholder">
                        <?= strtoupper(mb_substr($admin['nomUser'],0,1)).strtoupper(mb_substr($admin['prenomUser'],0,1)) ?>
                    </div>
                <?php endif; ?>
                <button class="change-photo-btn" onclick="openModal('avatar')" title="Change photo">📷</button>
            </div>
            <div class="hero-info">
                <div class="hero-name"><?= htmlspecialchars($admin['nomUser'].' '.$admin['prenomUser']) ?></div>
                <div class="hero-email"><?= htmlspecialchars($admin['email']) ?></div>
                <span class="hero-badge">👑 Administrator</span>
                <div class="hero-meta">Member since <?= $memberSince ?></div>
                <div class="hero-id">ID #<?= $admin['idUser'] ?></div>
            </div>
        </div>

        <!-- ── Full Name Section ── -->
        <div class="profile-section">
            <div class="ps-header">
                <div class="ps-title">
                    <div class="ps-icon">👤</div>
                    Full Name
                </div>
                <button class="btn-edit-section" onclick="openModal('name')">✏ Edit</button>
            </div>
            <div class="ps-body">
                <div class="info-row">
                    <div class="info-field">
                        <label>Last Name</label>
                        <p><?= htmlspecialchars($admin['nomUser']) ?></p>
                    </div>
                    <div class="info-field">
                        <label>First Name</label>
                        <p><?= htmlspecialchars($admin['prenomUser']) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Email Section ── -->
        <div class="profile-section">
            <div class="ps-header">
                <div class="ps-title">
                    <div class="ps-icon">✉️</div>
                    Email Address
                </div>
                <button class="btn-edit-section" onclick="openModal('email')">✏ Edit</button>
            </div>
            <div class="ps-body">
                <div class="info-row">
                    <div class="info-field">
                        <label>Email</label>
                        <p><?= htmlspecialchars($admin['email']) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Password Section ── -->
        <div class="profile-section">
            <div class="ps-header">
                <div class="ps-title">
                    <div class="ps-icon">🔒</div>
                    Password
                </div>
                <button class="btn-edit-section" onclick="openModal('password')">✏ Change</button>
            </div>
            <div class="ps-body">
                <div class="info-row">
                    <div class="info-field">
                        <label>Current Password</label>
                        <p>••••••••••••</p>
                    </div>
                    <div class="info-field">
                        <label>Last Updated</label>
                        <p style="color: var(--brown-pale);">—</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Photo Section ── -->
        <div class="profile-section">
            <div class="ps-header">
                <div class="ps-title">
                    <div class="ps-icon">🖼</div>
                    Profile Photo
                </div>
                <button class="btn-edit-section" onclick="openModal('avatar')">✏ Change</button>
            </div>
            <div class="ps-body" style="display:flex; align-items:center; gap:16px;">
                <?php if (!empty($admin['image'])): ?>
                    <img src="../uploads/users/<?= htmlspecialchars($admin['image']) ?>"
                         style="width:56px;height:56px;border-radius:50%;object-fit:cover;border:2.5px solid var(--brown-pale);">
                <?php else: ?>
                    <div style="width:56px;height:56px;border-radius:50%;background:linear-gradient(135deg,var(--brown-dark),var(--brown-mid));display:flex;align-items:center;justify-content:center;font-size:22px;color:var(--gold);font-weight:700;">
                        <?= strtoupper(mb_substr($admin['nomUser'],0,1)) ?>
                    </div>
                <?php endif; ?>
                <div>
                    <p style="margin:0;font-size:13.5px;font-weight:500;color:var(--brown-dark);"><?= !empty($admin['image']) ? htmlspecialchars($admin['image']) : 'No photo uploaded' ?></p>
                    <p style="margin:2px 0 0;font-size:12px;color:var(--brown-pale);">JPEG, PNG, WEBP or GIF accepted</p>
                </div>
            </div>
        </div>

    </div><!-- /profile-page -->
</div><!-- /main -->

<!-- ─────────────── MODALS ─────────────── -->

<!-- Edit Name -->
<div class="modal-overlay" id="modal-name">
    <div class="modal">
        <button class="modal-close" onclick="closeModal('name')">✕</button>
        <div class="modal-title">Edit Full Name</div>
        <form method="POST">
            <input type="hidden" name="field" value="name">
            <div class="form-group">
                <label>Last Name</label>
                <input type="text" name="nomUser" value="<?= htmlspecialchars($admin['nomUser']) ?>" required>
            </div>
            <div class="form-group">
                <label>First Name</label>
                <input type="text" name="prenomUser" value="<?= htmlspecialchars($admin['prenomUser']) ?>" required>
            </div>
            <button type="submit" class="btn-save">Save Changes</button>
        </form>
    </div>
</div>

<!-- Edit Email -->
<div class="modal-overlay" id="modal-email">
    <div class="modal">
        <button class="modal-close" onclick="closeModal('email')">✕</button>
        <div class="modal-title">Edit Email Address</div>
        <form method="POST">
            <input type="hidden" name="field" value="email">
            <div class="form-group">
                <label>New Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($admin['email']) ?>" required>
            </div>
            <button type="submit" class="btn-save">Save Changes</button>
        </form>
    </div>
</div>

<!-- Edit Password -->
<div class="modal-overlay" id="modal-password">
    <div class="modal">
        <button class="modal-close" onclick="closeModal('password')">✕</button>
        <div class="modal-title">Change Password</div>
        <form method="POST">
            <input type="hidden" name="field" value="password">
            <div class="form-group">
                <label>Current Password</label>
                <input type="password" name="currentPassword" required placeholder="Enter current password">
            </div>
            <div class="form-group">
                <label>New Password</label>
                <input type="password" name="newPassword" id="newPwd" required placeholder="Min. 6 characters" oninput="updateStrength(this.value)">
                <div class="strength-bar-wrap"><div class="strength-bar" id="strengthBar"></div></div>
            </div>
            <div class="form-group">
                <label>Confirm New Password</label>
                <input type="password" name="confirmPassword" required placeholder="Repeat new password">
            </div>
            <button type="submit" class="btn-save">Update Password</button>
        </form>
    </div>
</div>

<!-- Change Avatar -->
<div class="modal-overlay" id="modal-avatar">
    <div class="modal">
        <button class="modal-close" onclick="closeModal('avatar')">✕</button>
        <div class="modal-title">Change Profile Photo</div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="field" value="avatar">
            <div class="avatar-preview-wrap" id="avatarPreviewWrap">
                <img id="avatarPreviewImg" src="#" alt="Preview">
                <p style="margin-top:8px;font-size:12px;color:var(--brown-light);">Preview</p>
            </div>
            <div class="avatar-drop-zone" id="dropZone" onclick="document.getElementById('avatarFileInput').click()">
                <input type="file" name="image" id="avatarFileInput" accept="image/*" onchange="previewAvatar(this)">
                <div class="drop-icon">📁</div>
                <p>Click to upload a photo</p>
                <p style="margin-top:4px;font-size:11px;">JPEG, PNG, WEBP or GIF · Max 5 MB</p>
            </div>
            <button type="submit" class="btn-save" id="btnUpload" disabled>Upload Photo</button>
        </form>
    </div>
</div>

<script>
function openModal(type) {
    document.getElementById('modal-' + type).classList.add('open');
}
function closeModal(type) {
    document.getElementById('modal-' + type).classList.remove('open');
}
document.querySelectorAll('.modal-overlay').forEach(el => {
    el.addEventListener('click', function(e) {
        if (e.target === this) this.classList.remove('open');
    });
});

function updateStrength(val) {
    const bar = document.getElementById('strengthBar');
    let score = 0;
    if (val.length >= 6)  score++;
    if (val.length >= 10) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^a-zA-Z0-9]/.test(val)) score++;
    const colors = ['#ef4444','#f97316','#eab308','#22c55e','#10b981'];
    const widths = ['20%','40%','60%','80%','100%'];
    bar.style.width      = widths[score - 1] || '0';
    bar.style.background = colors[score - 1] || '#eee';
}

function previewAvatar(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('avatarPreviewImg').src = e.target.result;
            document.getElementById('avatarPreviewWrap').style.display = 'block';
            document.getElementById('dropZone').style.display = 'none';
            document.getElementById('btnUpload').disabled = false;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

document.querySelector(".menuicn").addEventListener("click", () => {
    document.querySelector(".navcontainer").classList.toggle("navclose");
});
</script>
</body>
</html>