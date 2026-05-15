<?php
session_start();
$order_ref = isset($_SESSION['derniere_commande']) ? $_SESSION['derniere_commande'] : "BK-INVITE";

// On vide le panier après avoir récupéré la référence
unset($_SESSION['panier']); 
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Suivi de commande - BookShop</title>
    <link rel="stylesheet" href="assests/css/style.css">
    <link rel="stylesheet" href="assests/css/style_suivi.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        /* Styles spécifiques pour le badge temps et l'icône */
        .arrival-badge {
            background: #e8f5e9;
            color: #27ae60;
            padding: 4px 8px;
            border-radius: 5px;
            font-weight: bold;
            margin-left: 5px;
        }
    </style>
</head>
<body style="background:#f4f7f6;">

<nav class="navbar">
    <div class="nav-container">
        <a href="index.php" class="logo">📚 BookShop</a>
        <div style="color:rgba(255,255,255,0.7);">Commande <?php echo $order_ref; ?></div>
    </div>
</nav>

<div class="tracking-container">
    <div class="status-card">
        <div class="status-header">
            <h2 style="color:#2c3e50;">Commande Validée ! 🚀</h2>
            <p style="color:#7f8c8d;">Votre nouvelle lecture est en préparation.</p>
        </div>

        <div class="stepper">
            <div class="step completed">
                <div class="step-icon"><i class="fas fa-check"></i></div>
                <div class="step-text">Confirmée</div>
            </div>
            <div class="step active">
                <div class="step-icon"><i class="fas fa-box"></i></div> 
                <div class="step-text">En préparation</div>
            </div>
            <div class="step">
                <div class="step-icon"><i class="fas fa-motorcycle"></i></div>
                <div class="step-text">En chemin</div>
            </div>
            <div class="step">
                <div class="step-icon"><i class="fas fa-home"></i></div>
                <div class="step-text">Livré</div>
            </div>
        </div>
    </div>

    <div id="map-container" style="position:relative; height:450px; border-radius:15px; overflow:hidden; box-shadow:0 10px 25px rgba(0,0,0,0.1);">
        <div id="delivery-map" style="height: 100%; width: 100%;"></div>
        
        <div class="courier-card" style="position:absolute; bottom:20px; left:20px; right:20px; background:white; padding:15px; border-radius:12px; display:flex; align-items:center; z-index:1000; box-shadow:0 5px 15px rgba(0,0,0,0.2);">
            <img src="https://ui-avatars.com/api/?name=Ahmed+Livreur&background=27ae60&color=fff" style="width:50px; border-radius:50%; margin-right:15px;">
            <div style="flex:1;">
                <strong style="display:block;">Ahmed - Livreur Partenaire</strong>
                <span style="font-size:0.9rem; color:#666;">
                    🚲 Arrivée prévue à : <span id="arrival-time" class="arrival-badge">--:--</span>
                </span>
            </div>
            <a href="tel:12345678" style="background:#27ae60; color:white; width:40px; height:40px; border-radius:50%; display:flex; align-items:center; justify-content:center; text-decoration:none;"><i class="fas fa-phone"></i></a>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    // 1. CALCUL DU TEMPS RÉEL DYNAMIQUE (Max 40 min)
    function updateDeliveryTime() {
        const now = new Date();
        // Délai aléatoire entre 20 et 40 minutes
        const waitMinutes = Math.floor(Math.random() * (40 - 20 + 1)) + 20;
        
        now.setMinutes(now.getMinutes() + waitMinutes);

        const hours = now.getHours().toString().padStart(2, '0');
        const minutes = now.getMinutes().toString().padStart(2, '0');

        document.getElementById('arrival-time').innerText = hours + ":" + minutes + " (dans " + waitMinutes + " min)";
    }

    // 2. INITIALISATION DE LA CARTE
    var map = L.map('delivery-map').setView([36.8065, 10.1815], 14);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

    var bikeIcon = L.icon({
        iconUrl: 'https://cdn-icons-png.flaticon.com/512/3194/3194781.png',
        iconSize: [40, 40]
    });

    L.marker([36.8065, 10.1815], {icon: bikeIcon}).addTo(map)
        .bindPopup('Ahmed prépare votre colis !')
        .openPopup();

    // Lancer le calcul du temps au chargement
    updateDeliveryTime();
</script>

</body>
</html>