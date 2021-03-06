<?php
use Afup\Site\Forum\Inscriptions;
use Afup\Site\Forum\Forum;

require_once dirname(__FILE__) .'/../../../sources/Afup/Bootstrap/Http.php';

if (
    !isset($_GET['ref'])
    ||
    !(
        preg_match('`ins-([0-9]+)`', $_GET['ref'], $matches)
        ||
        preg_match('`elephpant-([0-9]+)`', $_GET['ref'], $matches)
    )
) {
    die('Missing ref');
}

$forum_inscriptions = new Inscriptions($bdd);
$forum = new Forum($bdd);
$forumId = isset($_GET['forum']) ? intval($_GET['forum']) : $forum->obtenirDernier();
$forumData = $forum->obtenir($forumId);

if (!isset($forumData['id']) || !$forumData['id']) {
    die('Forum not found');
}

$ref = $matches[1];
$inscription = $forum_inscriptions->obtenir($ref);

if (preg_match('`elephpant-([0-9]+)`', $_GET['ref'], $matches)) {
    $prix = 25;
    $action = 'elep';
} else {
    $prix = 100;
    $action = 'subs';
}
if (isset($_GET['prix'])) {
    $prix = intval($_GET['prix']);
}

require_once dirname(__FILE__).'/../../../dependencies/paybox/payboxv2.inc';
$paybox = new PAYBOX;
$paybox->set_langue('FRA');
$paybox->set_site($conf->obtenir('paybox|site'));
$paybox->set_rang($conf->obtenir('paybox|rang'));
$paybox->set_identifiant('83166771');

$paybox->set_total($prix * 100);
$paybox->set_cmd(strtr($forumData['path'], ['forum' => 'frm', 'phptour' => 'tour']) . '-' . $action . '-' . $ref . '-' . $prix);
$paybox->set_porteur($inscription['email']);

$paybox->set_effectue('https://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/paybox_effectue.php');
$paybox->set_refuse('https://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/paybox_refuse.php');
$paybox->set_annule('https://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/paybox_annule.php');
$paybox->set_erreur('https://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/paybox_erreur.php');

$paybox->set_wait(50000);
$paybox->set_boutpi('R&eacute;gler ' . $prix . ' &euro; par carte');
$paybox->set_bkgd('#FAEBD7');
$paybox->set_output('B');

preg_match('#<CENTER>(.*)</CENTER>#is', $paybox->paiement(), $r);
$r[1] = preg_replace('#<b>.*?</b>#', '', $r[1]);
$smarty->assign('paybox', str_ireplace('input type=submit', 'input type="submit" class="btn primary"', $r[1]));
$smarty->assign('inscription', $inscription);
$smarty->assign('original_ref', urlencode($_GET['ref']));
$smarty->assign('forum', $forumData);
$smarty->display('paybox_formulaire.html');


// https://afup.org/pages/phptournantes2017/paiement/?ref=FOO
