<?php
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'ROLE_ADMIN';
}

function checkOwner($ownerId) {
    return isset($_SESSION['user_id']) && $_SESSION['user_id'] == $ownerId;
}
?>