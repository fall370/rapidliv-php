<?php // pages/livreur/historique.php
$user = utilisateurConnecte();
$page_num = max(1,(int)($_GET['p']??1));
$par_page = 15; $offset = ($page_num-1)*$par_page;
$total_count = Database::queryOne("SELECT COUNT(*) AS nb FROM livraisons WHERE livreur_id=? AND statut='livree'", [$user['id']]);
$pagination = paginer((int)$total_count['nb'], $par_page, $page_num);
$livraisons = Database::query(
    "SELECT l.*, c.reference, c.adresse_livraison, c.total,
            CONCAT(u.prenom,' ',u.nom) AS client_nom, s.nom AS service_nom
     FROM livraisons l JOIN commandes c ON c.id=l.commande_id
     JOIN utilisateurs u ON u.id=c.client_id JOIN services s ON s.id=c.service_id
     WHERE l.livreur_id=? AND l.statut='livree' ORDER BY l.livree_le DESC LIMIT ? OFFSET ?",
    [$user['id'], $par_page, $offset]);
$gains_mois = Database::queryOne("SELECT COALESCE(SUM(gain_livreur),0) AS total FROM livraisons WHERE livreur_id=? AND MONTH(livree_le)=MONTH(NOW()) AND statut='livree'", [$user['id']]);
?>
<div class="page-header"><div><div class="page-title">Historique</div><div class="page-sub"><?= $pagination['total'] ?> livraison<?= $pagination['total']>1?'s':'' ?></div></div></div>
<div class="card-sm mb-20" style="background:var(--primary-light);border-color:var(--primary)">
  <div class="flex gap-16">
    <div><div class="metric-label">Gains ce mois</div><div class="metric-value text-primary"><?= formatMontant($gains_mois['total']) ?></div></div>
    <div><div class="metric-label">Livraisons ce mois</div><div class="metric-value"><?= Database::queryOne("SELECT COUNT(*) AS nb FROM livraisons WHERE livreur_id=? AND MONTH(livree_le)=MONTH(NOW()) AND statut='livree'", [$user['id']])['nb'] ?></div></div>
  </div>
</div>
<?php if (!$livraisons): ?>
<div class="card empty-state"><div class="empty-icon">📋</div><div class="empty-title">Aucune livraison</div><div class="empty-text">Votre historique apparaîtra ici</div></div>
<?php else: ?>
<div class="card"><div class="table-wrap"><table>
  <thead><tr><th>Commande</th><th>Service</th><th>Client</th><th>Adresse</th><th>Gain</th><th>Date</th></tr></thead>
  <tbody>
  <?php foreach ($livraisons as $l): ?>
  <tr>
    <td class="fw-600"><?= sanitize($l['reference']) ?></td>
    <td><?= sanitize($l['service_nom']) ?></td>
    <td><?= sanitize($l['client_nom']) ?></td>
    <td class="text-muted text-sm"><?= sanitize(substr($l['adresse_livraison'],0,30)).'...' ?></td>
    <td class="fw-600 text-primary"><?= formatMontant($l['gain_livreur']??0) ?></td>
    <td class="text-muted text-xs"><?= $l['livree_le']?formatDate($l['livree_le']):'—' ?></td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table></div></div>
<?php if ($pagination['total_pages']>1): ?>
<div class="pagination">
  <a href="?page=livreur_historique&p=<?=$page_num-1?>" class="page-link <?=!$pagination['a_precedent']?'disabled':''?>">← Préc.</a>
  <?php for($i=1;$i<=$pagination['total_pages'];$i++): ?><a href="?page=livreur_historique&p=<?=$i?>" class="page-link <?=$i===$page_num?'active':''?>"><?=$i?></a><?php endfor; ?>
  <a href="?page=livreur_historique&p=<?=$page_num+1?>" class="page-link <?=!$pagination['a_suivant']?'disabled':''?>">Suiv. →</a>
</div>
<?php endif; ?>
<?php endif; ?>
