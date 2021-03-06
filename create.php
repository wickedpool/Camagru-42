<?php

include_once 'db.php';
include_once 'escape.php';

if (empty($_POST[email]) || empty($_POST[login]) || empty($_POST[mdp])) {
	header("location: create_user.php?msg=Merci de remplir tous les champs.\n");
	exit;
} else if (strlen($_POST[mdp]) < 8) {
	header("location: create_user.php?msg=Le mot de passe doit contenir au moins 8 caracteres.\n");
	exit;
} else if ($_POST[mdp] != $_POST[remdp]) {
	header("location: create_user.php?msg=Les mots de passe ne sont pas identiques.\n");
	exit;
}

$log = Escape::bdd($_POST[login]);
try {
	$db = new PDO($DB_DSN, $DB_USER, $DB_PASSWORD);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$stmt = $db->prepare('SELECT COUNT(*) FROM membres WHERE login = :login');
	$stmt->bindParam(':login', $log, PDO::PARAM_STR);
	$stmt->execute();
} catch (PDOException $mess) {
	echo 'Error: '.$mess->getMessage();
	exit;
}
if ($stmt->fetchColumn()) {
	header("Location: create_user.php?msg=Login deja pris.\n");
	exit;
}
$passwd = hash('whirlpool', Escape::bdd($_POST['mdp']));
$hash = md5( rand(0,1000) );
$email = Escape::bdd($_POST[email]);
try {
	$stmt = $db->prepare('INSERT INTO membres (email, login, passwd, hash) VALUES (:email, :login, :passwd, :hash)');
	$stmt->bindParam(':email', $email, PDO::PARAM_STR);
	$stmt->bindParam(':login', $log, PDO::PARAM_STR);
	$stmt->bindParam(':passwd', $passwd, PDO::PARAM_STR);
	$stmt->bindParam(':hash', $hash, PDO::PARAM_STR);
	$stmt->execute();
} catch (PDOException $mess) {
	echo 'Error: '.$mess->getMessage();
	exit;
}

$to      = $email;
$subject = 'Signup | Verification';
$message = '

	Thanks for signing up!
	Your account has been created, you can login with the following credentials after you have activated your account by pressing the url below.

	Merci pour votre inscription!
	Votre compte a ete cree, vous pouvez maintenant vous connecter avec vos identifiants apres avoir active votre compte en cliquant sur le lien en dessous.

	------------------------
	Username: '.$log.'
	------------------------

	Please click this link to activate your account:
	http://localhost:8080/Camagru/verify.php?email='.$email.'&hash='.$hash.'

	';

$headers = 'From:wickedpool@camagru.42.fr' . "\r\n";
mail($to, $subject, $message, $headers);
$_SESSION['login']=$log;
header("Location: index.php?msg=Votre compte a ete cree. Merci de consulter vos mail pour activer votre compte.\n");

?>
