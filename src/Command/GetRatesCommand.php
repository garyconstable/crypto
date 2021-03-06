<?php

namespace App\Command;

use App\Entity\Rates;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Psr\Log\LoggerInterface;

use Coinbase\Wallet\Client;
use Coinbase\Wallet\Configuration;
use Symfony\Component\Validator\Constraints\Date;
use \Swift_Mailer;

class GetRatesCommand extends Command
{
    protected static $defaultName = 'zoinbase:get:rates';

    private $apiKey = null;
    private $apiSecret = null;
    private $client = null;
    private $mailer = null;
    private $entityManager;
    private $cryptos = ['BTC', 'ETH'];
    private $fiats = ['GBP'];

    /**
     * GetRatesCommand constructor.
     * ==
     * @param ContainerInterface $container
     * @param Swift_Mailer $mailer
     */
    public function __construct(
        ContainerInterface $container,
        Swift_Mailer $mailer
    ) {
        parent::__construct();
        $this->entityManager = $container->get('doctrine')->getManager();
        $this->apiKey = getenv('COINBASE_KEY');
        $this->apiSecret = getenv('COINBASE_SECRET');
        $this->mailer = $mailer;
    }

    /**
     * ==
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configuration = Configuration::apiKey($this->apiKey, $this->apiSecret);
        $this->client = Client::create($configuration);
        $this->getCrypto();
        $this->emailMinMax();
    }

    /**
     * ==
     * @param string $currency1
     * @param string $currency2
     * @return float
     */
    public function getBuyRate($currency1 = 'BTC', $currency2 = 'GBP')
    {
        $data = $this->client->getBuyPrice($currency1 . '-' . $currency2);
        $amount = $data->getAmount();
        return !empty($amount) ? $amount : 0.00;
    }

    /**
     * ==
     * @param string $currency1
     * @param string $currency2
     * @return float
     */
    public function getSellRate($currency1 = 'BTC', $currency2 = 'GBP')
    {
        $data = $this->client->getSellPrice($currency1 . '-' . $currency2);
        $amount = $data->getAmount();
        return !empty($amount) ? $amount : 0.00;
    }

    public function getSpotPrice($currency1 = 'BTC', $currency2 = 'GBP')
    {
        $data = $this->client->getSpotPrice($currency1 . '-' . $currency2);
        $amount = $data->getAmount();
        return !empty($amount) ? $amount : 0.00;
    }

    /**
     * ==
     * @param array $data
     * @param bool $die
     */
    public static function d($data = [], $die = true)
    {
        echo '<pre>' . print_r($data, true) . '</pre>';
        if ($die) {
            die();
        }
    }

    /**
     * List supported currencies
     * ==
     * @param null $client
     */
    public function listCurrencies($client = null)
    {
        $currencies = $client->getCurrencies();
        $this->d($currencies, 0);
    }

    /**
     * ==
     * @param null $client
     * @param string $currency
     * @param array $params
     * @return mixed
     */
    public function listExchangeRates($client = null, $currency = "GBP", $params = [])
    {
        return $client->getExchangeRates($currency, $params);
    }

    /**
     * Get and Save the crypo's
     * ==
     * @throws \Exception
     */
    public function getCrypto()
    {
        foreach ($this->cryptos as $crypto) {
            $types = array(
                'buy' => $this->getBuyRate($crypto),
                'sell' => $this->getSellRate($crypto),
                'spot' => $this->getSpotPrice($crypto),
            );

            foreach ($types as $key => $value) {
                $r = new Rates();
                $r->setCurrency($crypto);
                $r->setCurrency2('GBP');
                $r->setValue($value);
                $r->setDateAdd(new \DateTime());
                $r->setType($key);
                $this->entityManager->persist($r);
                $this->entityManager->flush();
            }
        }
    }

    /**
     * Get the fiats
     * ==
     * @throws \Exception
     */
    public function getFiats()
    {
        foreach ($this->fiats as $fiat) {
            $data = $this->listExchangeRates($this->client, $fiat);

            foreach ($this->cryptos as $crypto) {
                $value = $data['rates'][$crypto];

                $r = new Rates();
                $r->setCurrency($fiat);
                $r->setCurrency2($crypto);
                $r->setValue($value);
                $r->setDateAdd(new \DateTime());
                $this->entityManager->persist($r);
                $this->entityManager->flush();
            }
        }
    }

    /**
     * ==
     */
    public function emailMinMax()
    {

        $sql = " SELECT `value`  FROM `rates` where `currency` = 'BTC' and `type` = 'buy'  ORDER BY id DESC  LIMIT 1;";
        $stmt = $this->entityManager->getConnection()->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetchAll();
        $buy = isset($data[0]['value']) ? $data[0]['value'] : 0.00;

        $sql = " SELECT `value`  FROM `rates` where `currency` = 'BTC' and `type` = 'sell'  ORDER BY id DESC  LIMIT 1;";
        $stmt = $this->entityManager->getConnection()->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetchAll();
        $sell = isset($data[0]['value']) ? $data[0]['value'] : 0.00;

        $sql = " SELECT `value`  FROM `rates` where `currency` = 'BTC' and `type` = 'spot'  ORDER BY id DESC  LIMIT 1;";
        $stmt = $this->entityManager->getConnection()->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetchAll();
        $spot = isset($data[0]['value']) ? $data[0]['value'] : 0.00;

        $transactions = $this->calculateTransaction($sell);

        $body = '<p><strong>Buy</strong>: &pound;' . $buy . '</p>';
        $body .= '<p><strong>Sell</strong>: &pound;' . $sell . '</p>';
        $body .= '<p><strong>Spot</strong>: &pound;' . $spot . '</p>';

        if (!empty($transactions)) {
            $body .= '<hr>';
            $body .= '<h3>Transactions:</h3>';
            $body .= '<table cellpadding="10" cellspacing="10">';
            $body .= '<thead>';
            $body .= '<tr>';
            $body .= '<th>Cost</th>';
            $body .= '<th>Fee</th>';
            $body .= '<th>GBP</th>';
            $body .= '<th>BTC</th>';
            $body .= '<th>Buy</th>';
            $body .= '<th>Sell</th>';
            $body .= '<th>Value</th>';
            $body .= '<th>Fee</th>';
            $body .= '<th>Subtotal</th>';
            $body .= '<th>Profit / Loss</th>';
            $body .= '</tr>';
            $body .= '</thead>';
            $body .= '<tbody>';
            foreach ($transactions as $transaction) {
                $body .= '<tr>';
                $body .= '<td>' . $transaction['cost'] . '</td>';
                $body .= '<td>' . $transaction['fee'] . '</td>';
                $body .= '<td>' . $transaction['gbp'] . '</td>';
                $body .= '<td>' . $transaction['btc'] . '</td>';
                $body .= '<td>' . $transaction['buy_btc_rate'] . '</td>';
                $body .= '<td>' . $transaction['sell_btc_rate'] . '</td>';
                $body .= '<td>' . $transaction['value_gbp'] . '</td>';
                $body .= '<td>' . $transaction['fee_sell'] . '</td>';
                $body .= '<td>' . $transaction['subtotal'] . '</td>';
                $body .= '<td>' . $transaction['profit_loss'] . '</td>';
                $body .= '</tr>';
            }
            $body .= '</tbody>';
            $body .= '</table>';
        }


        $sales =  $this->entityManager->getRepository('App:Sales')->findAll();

        if (!empty($sales)) {
            $body .= '<hr>';
            $body .= '<h3>Sales:</h3>';
            $body .= '<table cellpadding="10" cellspacing="10">';
            $body .= '<thead>';
            $body .= '<tr>';
            $body .= '<th>Cost</th>';
            $body .= '<th>Fee</th>';
            $body .= '<th>GBP</th>';
            $body .= '<th>BTC</th>';
            $body .= '<th>Buy</th>';
            $body .= '<th>Sell</th>';
            $body .= '<th>Value</th>';
            $body .= '<th>Fee</th>';
            $body .= '<th>Subtotal</th>';
            $body .= '<th>Profit / Loss</th>';
            $body .= '</tr>';
            $body .= '</thead>';
            $body .= '<tbody>';
            foreach ($sales as $transaction) {
                $body .= '<tr>';
                $body .= '<td>' . $transaction->getCost() . '</td>';
                $body .= '<td>' . $transaction->getFee() . '</td>';
                $body .= '<td>' . $transaction->getGbp(). '</td>';
                $body .= '<td>' . $transaction->getBtc() . '</td>';
                $body .= '<td>' . $transaction->getBuyRate() . '</td>';
                $body .= '<td>' . $transaction->getSellRate() . '</td>';
                $body .= '<td>' . $transaction->getValueGbp() . '</td>';
                $body .= '<td>' . $transaction->getFeeSell() . '</td>';
                $body .= '<td>' . $transaction->getSubtotal() . '</td>';
                $body .= '<td>' . $transaction->getProfitLoss() . '</td>';
                $body .= '</tr>';
            }
            $body .= '</tbody>';
            $body .= '</table>';
        }

        $message = (new \Swift_Message('Current Bitcoin Rates.'))
            ->setFrom('info@garyconstable.dev')
            ->setTo('garyconstable80@gmail.com')
            ->setBody(
                $body,
                'text/html'
            );

        $this->mailer->send($message);
    }

    /**
     * ==
     * @param float $sell_btc_rate
     * @return array
     */
    public function calculateTransaction($sell_btc_rate = 0.00)
    {
        $data = [];
        $transactions = $this->entityManager->getRepository('App:Transaction')->findAll();

        foreach ($transactions as $transaction) {
            $tmp = [];
            $tmp['cost'] = $transaction->getCost();
            $tmp['fee'] = $transaction->getFee();
            $tmp['gbp'] = $transaction->getGbp();
            $tmp['btc'] = $transaction->getBtc();
            $tmp['buy_btc_rate'] = $transaction->getBuyRate();
            $tmp['sell_btc_rate'] = $sell_btc_rate;
            $tmp['value_gbp'] = $sell_btc_rate * $tmp['btc'];
            $tmp['fee_sell'] = $transaction->getFee();
            $tmp['subtotal'] = $tmp['value_gbp'] - $tmp['fee_sell'];
            $tmp['profit_loss'] = $tmp['subtotal'] - $tmp['cost'];
            $data[] = $tmp;
        }
        return $data;
    }
}
