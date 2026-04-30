<?php
// includes/nav.php
// Set $activePage before including
?>
<header>
    <div class="logosec">
        <img class="menuicn" id="menuicn" src="https://img.icons8.com/ios-filled/50/menu--v1.png" alt="menu">
        <div class="logo">📚 BookShop Admin</div>
    </div>
    <span class="admin-info">Hello, <?= htmlspecialchars($_SESSION['nomUser']) ?> 👋</span>
    <a class="logout-btn" href="../logout.php">Log out</a>
</header>

<div class="main-container">
    <div class="navcontainer" id="navcontainer">
        <nav class="nav">
            <div class="nav-upper-options">
                <a class="nav-option" href="dashboard.php">
                    <img class="nav-img" src="https://img.icons8.com/?size=100&id=10245&format=png&color=000000"><h3>Dashboard</h3>
                </a>
                <a class="nav-option" href="add-book.php">
                    <img class="nav-img" src="https://img.icons8.com/?size=100&id=11255&format=png&color=000000"><h3>Add Book</h3>
                </a>
                <a class="nav-option" href="books.php">
                    <img class="nav-img" src="https://img.icons8.com/ios-filled/50/book.png"><h3>All Books</h3>
                </a>
                <a class="nav-option" href="authors.php">
                    <img class="nav-img" src="https://img.icons8.com/?size=100&id=100103&format=png&color=000000"><h3>Authors</h3>
                </a>
                <a class="nav-option" href="categories.php">
                    <img class="nav-img" src="https://img.icons8.com/?size=100&id=8416&format=png&color=000000"><h3>Categories</h3>
                </a>
                <a class="nav-option" href="review.php">
                    <img class="nav-img" src="https://img.icons8.com/ios-filled/50/star.png"><h3>Reviews</h3>
                </a>
                <a class="nav-option" href="orders.php">
                    <img class="nav-img" src="https://img.icons8.com/?size=100&id=11271&format=png&color=000000"><h3>Orders</h3>
                </a>
                <a class="nav-option" href="users.php">
                    <img class="nav-img" src="https://img.icons8.com/?size=100&id=23265&format=png&color=000000"><h3>Users</h3>
                </a>
                <a class="nav-option logout-nav" href="../logout.php">
                    <img class="nav-img" src="https://img.icons8.com/ios-filled/50/exit.png"><h3>Log out</h3>
                </a>
            </div>
        </nav>
    </div>
    <!-- content starts here -->
