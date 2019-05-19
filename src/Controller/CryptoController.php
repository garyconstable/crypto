<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class CryptoController extends AbstractController
{
    private $entityManager;

    /**
     * ==
     * @param array $data
     * @param bool $die
     */
    public static function d($data = [], $die = true)
    {
        echo '<pre>'.print_r($data, true).'</pre>';
        if ($die) {
            die();
        }
    }

    /**
     * ==
     * @return mixed
     */
    public function getRate()
    {
        $this->entityManager = $this->getDoctrine()->getManager();
        $sql = "SELECT * FROM `rates` where `currency` = 'BTC' ORDER BY `id` DESC LIMIT 1;";
        $stmt = $this->entityManager->getConnection()->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetchAll();
        return $data[0]['value'];
    }

    /**
     * ==
     * @return array
     */
    public function getSell()
    {
        $this->entityManager = $this->getDoctrine()->getManager();
        $sql = " SELECT `value`  FROM `rates` where `currency` = 'BTC' and `type` = 'sell'  ORDER BY id DESC  LIMIT 1;";
        $stmt = $this->entityManager->getConnection()->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetchAll();
        $max = $data[0]['value'];

        $resp = [
            'gbp' => $max,
            'btc' => 1 / $max
        ];

        return $resp;
    }

    /**
     * ==
     * @return array
     */
    public function getBuy()
    {
        $this->entityManager = $this->getDoctrine()->getManager();
        $sql = " SELECT `value`  FROM `rates` where `currency` = 'BTC' and `type` = 'buy'  ORDER BY `value` ASC  LIMIT 1;";
        $stmt = $this->entityManager->getConnection()->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetchAll();
        $min = $data[0]['value'];

        $resp = [
            'gbp' => $min,
            'btc' => 1 / $min
        ];

        return $resp;
    }

    /**
     * @Route("/crypto", name="crypto")
     */
    public function index()
    {
        $buy_data       = $this->getBuy();
        $buy_btc        = $buy_data['btc'];
        $buy_btc_rate   = $buy_data['gbp'];

        $sell_data      = $this->getSell();
        $sell_btc_rate  = $sell_data['gbp'];

        $costs = [20, 100, 150, 200, 250, 300, 350, 400, 450, 500/*, 550, 600, 650, 700, 850, 900, 950, 1000*/];
        $data = [];

        $current_rate   = $this->getRate();
        $min_rate       = $buy_btc_rate;
        $max_rate       = $sell_btc_rate;

        foreach ($costs as $cost) {
            $tmp = [];
            $tmp['cost'] = $cost;

            if ($cost > 20) {
                $fee = 2.99;
            } else {
                $fee = 1.49;
            }
            $tmp['fee'] = $fee;
            $tmp['gbp'] = $cost - $fee;
            $tmp['btc'] = $buy_btc * $tmp['gbp'];
            $tmp['buy_btc_rate'] = $buy_btc_rate;
            $tmp['sell_btc_rate'] = $sell_btc_rate;
            $tmp['value_gbp'] = $sell_btc_rate * $tmp['btc'];
            $tmp['fee_sell'] = $fee;
            $tmp['subtotal'] = $tmp['value_gbp'] -  $tmp['fee_sell'];
            $tmp['profit_loss'] = $tmp['subtotal'] - $tmp['cost'];

            $data[] = $tmp;
        }

        //$this->d($data);

        return $this->render('crypto/index.html.twig', [
            'controller_name' => 'CryptoController',
            'data' => $data,
            'current_rate' => $current_rate,
            'min_rate' => $min_rate,
            'max_rate' => $max_rate
        ]);
    }
}
