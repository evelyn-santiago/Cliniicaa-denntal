<?php
// Redirigir a login.html si no hay sesión activa, o si se prefiere una página de inicio específica
header("Location: index.html");
exit();
?>
