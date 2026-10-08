<?php
function game_card(array $g): string {
    $ok = bookable($g['etat']);
    $img = game_img($g);
    $style = '--c:' . e($g['couleur']) . ($img ? ';background-image:url(\'' . e($img) . '\')' : '');
    $h = '<article class="game"><div class="pic" style="' . $style . '">' . ($img ? '' : e($g['emoji'])) . etat_badge($g['etat']) . '</div><div class="body">';
    $h .= '<h3><a href="' . url('jeu.php?id=' . (int)$g['id']) . '" style="text-decoration:none">' . e($g['nom']) . '</a></h3><p>' . e(mb_strimwidth($g['description'], 0, 110, '…')) . '</p>';
    $h .= '<div class="meta"><span>👥 ' . (int)$g['capacite'] . ' places</span><span>⏱ ' . (int)$g['duree_session'] . ' min</span><span>🎂 ' . (int)$g['age_min'] . '+ ans</span></div>';
    $h .= '<div class="price">' . money($g['tarif']) . '<small class="muted"> / pers.</small></div><div class="actions">';
    $h .= '<a class="btn sm ' . ($ok ? '' : 'off') . '" href="' . url('reserver.php?jeu=' . (int)$g['id']) . '">Réserver</a>';
    $h .= '<a class="btn sm alt ' . ($ok ? '' : 'off') . '" href="' . url('acheter.php?jeu=' . (int)$g['id']) . '">Acheter un billet</a></div></div></article>';
    return $h;
}
