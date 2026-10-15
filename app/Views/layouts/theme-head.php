<?php // Authenticated pages use the account preference; public pages use this browser's choice. ?>
<meta name="color-scheme" content="light dark">
<?php if (!isset($usuario)): ?>
<script>(() => { try { const theme = localStorage.getItem('uaimoney_theme'); if (theme === 'light' || theme === 'dark') document.documentElement.setAttribute('data-bs-theme', theme); } catch (_) {} })();</script>
<?php endif; ?>
