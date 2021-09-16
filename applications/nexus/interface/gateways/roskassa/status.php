<?php
/**
 * @brief		Ros-Kassa Gateway
 * @author		Syntlex
 * @version		1.0.0
 */

require_once '../../../../../init.php';
\IPS\Session\Front::i();

try
{
	$transaction = \IPS\nexus\Transaction::load(\IPS\Request::i()->order_id);
	
	if ($transaction->status !== \IPS\nexus\Transaction::STATUS_PENDING)
	{
		throw new \OutofRangeException;
	}
}
catch (\OutOfRangeException $e)
{
	\IPS\Output::i()->redirect(\IPS\Http\Url::internal("app=nexus&module=payments&controller=checkout&do=transaction&id=&t=" . \IPS\Request::i()->order_id, 'front', 'nexus_checkout', \IPS\Settings::i()->nexus_https));
}

if (isset(\IPS\Request::i()->order_id) && isset(\IPS\Request::i()->sign))
{
	$err = false;
	$message = '';
	$language = \IPS\Lang::load(\IPS\Lang::defaultLanguage());
	$settings = json_decode($transaction->method->settings, TRUE);


    if (!$err)
	{
		// проверка статуса
		if ( isset(\IPS\Request::i()->sign)) {

            try {
                $maxMind = NULL;
                if (\IPS\Settings::i()->maxmind_key) {
                    $maxMind = new \IPS\nexus\Fraud\MaxMind\Request;
                    $maxMind->setTransaction($transaction);
                    $maxMind->setTransactionType('roskassa');
                }

                $transaction->checkFraudRulesAndCapture($maxMind);
                $transaction->sendNotification();
                \IPS\Session::i()->setMember($transaction->invoice->member);
            } catch (\Exception $e) {
                \IPS\Output::i()->redirect($transaction->invoice->checkoutUrl()->setQueryString(array('_step' => 'checkout_pay', 'err' => $e->getMessage())));
            }

        }
        else {
            $message .= $language->get('roskassa_email_message2') . "\n";
            $err = true;
        }
	}

	if ($err)
	{
		$to = $settings['EmailError'];

		if (!empty($to))
		{
			$message = $language->get('roskassa_email_message1') . "\n\n" . $message . "\n" ;
			$headers = "From: no-reply@" . $_SERVER['HTTP_HOST'] . "\r\n" . 
			"Content-type: text/plain; charset=utf-8 \r\n";
			mail($to, $language->get('roskassa_email_subject'), $message, $headers);
		}
		
		exit(\IPS\Request::i()->m_orderid . '|error| ' . $message);
	}
	else
	{
		exit('YES');
	}
}

?>