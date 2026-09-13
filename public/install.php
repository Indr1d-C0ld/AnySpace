<?php
// L'installer via browser è stato disattivato durante la bonifica del
// 2026-09-13: scriveva credenziali e schema del DB direttamente dal form
// HTML (endpoint non autenticato) e rigenerava core/config.php come sorgente
// PHP letterale. L'installazione ora si fa da riga di comando, coerente con
// gli altri strumenti in core/tools/.
http_response_code(403);
?>
<!DOCTYPE html>
<html>
<head><title>Installazione AnySpace</title></head>
<body style="font-family: sans-serif; max-width: 640px; margin: 40px auto;">
<h1>Installazione via browser disattivata</h1>
<p>Per motivi di sicurezza l'installazione di AnySpace non si esegue più da questa pagina.</p>
<p>Da riga di comando sul server:</p>
<pre>php core/tools/install.php</pre>
</body>
</html>
