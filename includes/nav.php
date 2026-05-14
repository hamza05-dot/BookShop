<header>
    <div class="logosec">
        <img class="menuicn" id="menuicn" src="https://img.icons8.com/ios-filled/50/menu--v1.png" alt="menu">
         <a class="logo" href="../admin/dashboard.php">📚 BookShop Admin </a>
    </div>
    <div class="header-right">
        <span class="admin-info">Hello, <?= htmlspecialchars($_SESSION['nomUser']) ?> 👋</span>
        <a class="admin-avatar-link" href="<?= (strpos($_SERVER['PHP_SELF'], '/admin/') !== false) ? 'profile.php' : 'admin/profile.php' ?>" title="My Profile">
            <?php if (!empty($_SESSION['image'])): ?>
                <img class="admin-avatar-thumb"
                     src="<?= (strpos($_SERVER['PHP_SELF'], '/admin/') !== false) ? '' : 'admin/' ?>../uploads/users/<?= htmlspecialchars($_SESSION['image']) ?>"
                     alt="Profile">
            <?php else: ?>
                <div class="admin-avatar-initials">
                    <?= strtoupper(mb_substr($_SESSION['nomUser'] ?? 'A', 0, 1)) ?><?= strtoupper(mb_substr($_SESSION['prenomUser'] ?? '', 0, 1)) ?>
                </div>
            <?php endif; ?>
        </a>
        <a class="logout-btn" href="../logout.php">Log out</a>
    </div>
</header>

<style>
.header-right {
    display: flex;
    align-items: center;
    gap: 14px;
}
.admin-avatar-link {
    display: flex;
    align-items: center;
    text-decoration: none;
    flex-shrink: 0;
}
.admin-avatar-thumb {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    object-fit: cover;
    border: 2.5px solid var(--gold);
    box-shadow: 0 2px 10px rgba(201, 168, 76, 0.35);
    transition: transform 0.18s, box-shadow 0.18s;
}
.admin-avatar-thumb:hover {
    transform: scale(1.08);
    box-shadow: 0 4px 18px rgba(201, 168, 76, 0.55);
}
.admin-avatar-initials {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--brown-mid), var(--brown-light));
    color: var(--gold);
    font-size: 13px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2.5px solid rgba(201, 168, 76, 0.45);
    box-shadow: 0 2px 10px rgba(201, 168, 76, 0.25);
    letter-spacing: 0.5px;
    transition: transform 0.18s, box-shadow 0.18s;
}
.admin-avatar-initials:hover {
    transform: scale(1.08);
    box-shadow: 0 4px 18px rgba(201, 168, 76, 0.45);
}
</style>

<div class="main-container">
    <div class="navcontainer" id="navcontainer">
        <nav class="nav">
            <div class="nav-upper-options">
                <a class="nav-option <?= ($activePage ?? '') === 'dashboard' ? 'active' : '' ?>" href="dashboard.php">
                    <img class="nav-img" src="https://img.icons8.com/?size=100&id=10245&format=png&color=000000" alt="Dashboard">
                    <h3>Dashboard</h3>
                </a>
                <a class="nav-option <?= ($activePage ?? '') === 'books' ? 'active' : '' ?>" href="books.php">
                    <img class="nav-img" src="https://img.icons8.com/ios-filled/50/book.png" alt="Books">
                    <h3>All Books</h3>
                </a>
                <a class="nav-option <?= ($activePage ?? '') === 'authors' ? 'active' : '' ?>" href="authors.php">
                    <img class="nav-img" src="https://img.icons8.com/?size=100&id=100103&format=png&color=000000" alt="Authors">
                    <h3>Authors</h3>
                </a>
                <a class="nav-option <?= ($activePage ?? '') === 'categories' ? 'active' : '' ?>" href="categories.php">
                    <img class="nav-img" src="https://img.icons8.com/?size=100&id=8416&format=png&color=000000" alt="Categories">
                    <h3>Categories</h3>
                </a>
                <a class="nav-option <?= ($activePage ?? '') === 'reviews' ? 'active' : '' ?>" href="review.php">
                    <img class="nav-img" src="https://img.icons8.com/ios-filled/50/star.png" alt="Reviews">
                    <h3>Reviews</h3>
                </a>
                <a class="nav-option <?= ($activePage ?? '') === 'orders' ? 'active' : '' ?>" href="orders.php">
                    <img class="nav-img" src="https://img.icons8.com/?size=100&id=11271&format=png&color=000000" alt="Orders">
                    <h3>Orders</h3>
                </a>
                <a class="nav-option <?= ($activePage ?? '') === 'users' ? 'active' : '' ?>" href="users.php">
                    <img class="nav-img" src="https://img.icons8.com/?size=100&id=23265&format=png&color=000000" alt="Users">
                    <h3>Users</h3>
                </a>

                <!-- Profile nav item: shows avatar image or initials instead of a generic icon -->
                <a class="nav-option <?= ($activePage ?? '') === 'profile' ? 'active' : '' ?>" href="profile.php">
                    <?php if (!empty($_SESSION['image'])): ?>
                        <img class="nav-img nav-avatar-img"
                             src="../uploads/users/<?= htmlspecialchars($_SESSION['image']) ?>"
                             alt="My Profile">
                    <?php else: ?>
                        <div class="nav-avatar-initials">
                            <?= strtoupper(mb_substr($_SESSION['nomUser'] ?? 'A', 0, 1)) ?><?= strtoupper(mb_substr($_SESSION['prenomUser'] ?? '', 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                    <h3>My Profile</h3>
                </a>

                <a class="nav-option logout-nav" href="../logout.php">
                    <img class="nav-img" src="https://img.icons8.com/ios-filled/50/exit.png" alt="Log out">
                    <h3>Log out</h3>
                </a>
            </div>
        </nav>
    </div>

<style>
/* Nav profile avatar image */
.nav-avatar-img {
    width: 26px !important;
    height: 26px !important;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid rgba(201, 168, 76, 0.5);
    filter: none !important;   /* override the global invert filter */
    flex-shrink: 0;
}
/* Nav profile initials fallback */
.nav-avatar-initials {
    width: 26px;
    height: 26px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--brown-mid), var(--brown-light));
    color: var(--gold);
    font-size: 10px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid rgba(201, 168, 76, 0.45);
    flex-shrink: 0;
    letter-spacing: 0.3px;
}
.nav-option.active .nav-avatar-initials {
    border-color: var(--gold);
    box-shadow: 0 0 0 2px rgba(201,168,76,0.25);
}
</style>