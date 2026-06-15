<?php
// pages/connexion.php
?>
<div class="auth-shell">
  <section class="card auth-card">
    <div class="auth-brand">
      <span class="logo-mark"><i class="fa-solid fa-bolt"></i></span>
      <span>Rapid<span>Liv</span></span>
    </div>
    <h1 class="auth-title">Bon retour parmi nous</h1>
    <p class="auth-subtitle">Connectez-vous pour gérer vos commandes, vos livraisons ou votre espace administrateur.</p>

    <div id="form-error" class="flash flash-error" style="display:none;margin-bottom:16px"></div>

    <div class="form-group">
      <label class="form-label required">Email</label>
      <input type="email" id="email" class="form-input" placeholder="votre@email.sn" autocomplete="email">
    </div>
    <div class="form-group">
      <label class="form-label required">Mot de passe</label>
      <input type="password" id="mdp" class="form-input" placeholder="••••••••" autocomplete="current-password">
    </div>

    <button class="btn btn-primary btn-full" onclick="connexion()" id="btn-login">
      <i class="fa-solid fa-arrow-right-to-bracket"></i> Se connecter
    </button>

    <div class="section-sep"></div>

    <div class="text-center text-sm">
      Pas encore de compte ?
      <a href="index.php?page=inscription">S'inscrire gratuitement</a>
    </div>

    <div class="card-sm mt-16" style="background:#F8FAFC!important">
      <div class="metric-label"></div>
      <div class="text-xs text-muted" style="line-height:1.7">
        <br>
        <br>
        <br>
        <strong></strong>
      </div>
    </div>
  </section>

  <aside class="auth-visual" aria-hidden="true">
    <img src="https://images.unsplash.com/photo-1607082349566-187342175e2f?auto=format&fit=crop&w=1400&q=80" alt="">
    <div class="auth-proof">
      <div class="auth-proof-title">Une expérience de livraison claire, rapide et suivie.</div>
      <div class="auth-proof-grid">
        <div class="auth-proof-item">
          <div class="auth-proof-number">35 min</div>
          <div class="auth-proof-label">délai moyen</div>
        </div>
        <div class="auth-proof-item">
          <div class="auth-proof-number">4.8/5</div>
          <div class="auth-proof-label">satisfaction</div>
        </div>
        <div class="auth-proof-item">
          <div class="auth-proof-number">7j/7</div>
          <div class="auth-proof-label">service actif</div>
        </div>
      </div>
    </div>
  </aside>
</div>

<script>
function connexion() {
  const email = document.getElementById('email').value.trim();
  const mdp   = document.getElementById('mdp').value;
  const btn   = document.getElementById('btn-login');
  const err   = document.getElementById('form-error');
  err.style.display = 'none';
  if (!email || !mdp) { afficherErreur('Remplissez tous les champs'); return; }
  btn.disabled = true; btn.textContent = 'Connexion...';
  api('connexion', {email, mot_de_passe: mdp})
    .then(d => { window.location.href = d.redirect; })
    .catch(e => { afficherErreur(e.message); btn.disabled=false; btn.textContent='Se connecter'; });
}
function afficherErreur(msg) {
  const el = document.getElementById('form-error');
  el.textContent = msg; el.style.display = 'flex';
}
document.getElementById('mdp').addEventListener('keydown', e => { if(e.key==='Enter') connexion(); });
</script>
