<?php // pages/inscription.php ?>
<div class="auth-shell">
  <section class="card auth-card">
    <div class="auth-brand">
      <span class="logo-mark"><i class="fa-solid fa-bolt"></i></span>
      <span>Rapid<span>Liv</span></span>
    </div>
    <h1 class="auth-title">Créez votre compte RapidLiv</h1>
    <p class="auth-subtitle">Accédez aux services proches de vous, suivez vos commandes et recevez vos livraisons avec un parcours simple.</p>

    <div id="form-error" class="flash flash-error" style="display:none;margin-bottom:16px"></div>
    <div class="form-row">
      <div class="form-group"><label class="form-label required">Prénom</label><input type="text" id="prenom" class="form-input" placeholder="Moussa"></div>
      <div class="form-group"><label class="form-label required">Nom</label><input type="text" id="nom" class="form-input" placeholder="Kouyaté"></div>
    </div>
    <div class="form-group"><label class="form-label required">Email</label><input type="email" id="email" class="form-input" placeholder="votre@email.sn"></div>
    <div class="form-group"><label class="form-label required">Téléphone</label><input type="tel" id="telephone" class="form-input" placeholder="+221 77 xxx xx xx"></div>
    <div class="form-group"><label class="form-label required">Mot de passe</label><input type="password" id="mdp" class="form-input" placeholder="6 caractères minimum"></div>
    <div class="form-group">
      <label class="form-label">Vous êtes</label>
      <div class="flex gap-8">
        <label class="role-choice">
          <input type="radio" name="role" value="client" <?= ($_GET['role'] ?? 'client') !== 'livreur' ? 'checked' : '' ?> onchange="actualiserChampsLivreur()" style="accent-color:var(--primary)"> Client
        </label>
        <label class="role-choice">
          <input type="radio" name="role" value="livreur" <?= ($_GET['role'] ?? '') === 'livreur' ? 'checked' : '' ?> onchange="actualiserChampsLivreur()" style="accent-color:var(--primary)"> Livreur
        </label>
      </div>
    </div>
    <div id="documents-livreur" class="card-sm" style="display:none;margin-bottom:16px;background:var(--primary-light)">
      <div class="fw-700 mb-8">Documents obligatoires du livreur</div>
      <div class="text-xs text-muted mb-12">L’administrateur vérifiera ces images avant d’autoriser les livraisons.</div>
      <div class="form-group">
        <label class="form-label required">Votre photo de profil</label>
        <input type="file" id="photo_profil" class="form-input" accept="image/jpeg,image/png,image/webp" capture="user">
        <div class="form-hint">Photo récente et visage clairement visible. JPG, PNG ou WEBP, 5 Mo maximum.</div>
      </div>
      <div class="form-group" style="margin-bottom:0">
        <label class="form-label required">Photo de votre carte d’identité</label>
        <input type="file" id="carte_identite" class="form-input" accept="image/jpeg,image/png,image/webp" capture="environment">
        <div class="form-hint">La carte doit être entière, nette et lisible.</div>
      </div>
    </div>
    <button class="btn btn-primary btn-full" onclick="inscrire()" id="btn-inscr">
      <i class="fa-solid fa-user-plus"></i> Créer mon compte
    </button>
    <div class="section-sep"></div>
    <div class="text-center text-sm">Déjà un compte ? <a href="index.php?page=connexion">Se connecter</a></div>
  </section>

  <aside class="auth-visual" aria-hidden="true">
    <img src="https://images.unsplash.com/photo-1593950315186-76a92975b60c?auto=format&fit=crop&w=1400&q=80" alt="">
    <div class="auth-proof">
      <div class="auth-proof-title">Des commandes locales, des livreurs identifiés, un suivi lisible.</div>
      <div class="auth-proof-grid">
        <div class="auth-proof-item">
          <div class="auth-proof-number">Dakar</div>
          <div class="auth-proof-label">zone active</div>
        </div>
        <div class="auth-proof-item">
          <div class="auth-proof-number">4 types</div>
          <div class="auth-proof-label">services livrés</div>
        </div>
        <div class="auth-proof-item">
          <div class="auth-proof-number">Code</div>
          <div class="auth-proof-label">confirmation client</div>
        </div>
      </div>
    </div>
  </aside>
</div>
<script>
function actualiserChampsLivreur() {
  const livreur = document.querySelector('input[name=role]:checked')?.value === 'livreur';
  document.getElementById('documents-livreur').style.display = livreur ? '' : 'none';
  document.getElementById('photo_profil').required = livreur;
  document.getElementById('carte_identite').required = livreur;
}

function inscrire() {
  const role = document.querySelector('input[name=role]:checked').value;
  const photo = document.getElementById('photo_profil').files[0];
  const identite = document.getElementById('carte_identite').files[0];
  const err = document.getElementById('form-error');
  err.style.display='none';

  if (role === 'livreur' && (!photo || !identite)) {
    err.textContent = 'La photo de profil et la carte d’identité sont obligatoires pour un livreur.';
    err.style.display = 'flex';
    return;
  }

  const data = new FormData();
  data.append('prenom', document.getElementById('prenom').value.trim());
  data.append('nom', document.getElementById('nom').value.trim());
  data.append('email', document.getElementById('email').value.trim());
  data.append('telephone', document.getElementById('telephone').value.trim());
  data.append('mot_de_passe', document.getElementById('mdp').value);
  data.append('role', role);
  if (photo) data.append('photo_profil', photo);
  if (identite) data.append('carte_identite', identite);

  const btn = document.getElementById('btn-inscr');
  btn.disabled=true; btn.textContent='Création...';
  api('inscription', data)
    .then(d => { window.location.href = d.redirect; })
    .catch(e => { err.textContent=e.message; err.style.display='flex'; btn.disabled=false; btn.textContent='Créer mon compte'; });
}
actualiserChampsLivreur();
</script>
