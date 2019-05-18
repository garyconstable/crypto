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


        $body  = '<p><strong>Buy</strong>: &pound;' . $buy . '</p>';
        $body .= '<p><strong>Sell</strong>: &pound;' . $sell . '</p>';
        $body .= '<p><strong>Spot</strong>: &pound;' . $spot . '</p>';

        $message = (new \Swift_Message('Current Bitcoin Rates.'))
            ->setFrom('info@garyconstable.dev')
            ->setTo('garyconstable80@gmail.com')
            ->setBody(
                $body,
                'text/html'
            );

        $this->mailer->send($message);
    }
}
