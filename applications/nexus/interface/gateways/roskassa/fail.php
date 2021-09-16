<?php
/**
 * @brief		Ros-Kassa Gateway
 * @author		Syntlex
 * @version		1.0.0
 */

require_once '../../../../../init.php';
\IPS\Session\Front::i();

$transaction = \IPS\nexus\Transaction::load(\IPS\Request::i()->order_id);
\IPS\Output::i()->redirect($transaction->url());
?>