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

class GetRatesCommand extends Command
{
    protected static $defaultName = 'zoinbase:get:rates';

    private $apiKey = "aVni2bEOeVAADMQl";
    private $apiSecret = "EI61xAnwwN1Uye2Oe1IwRNzbgjew4OJS";
    private $client = null;
    private $entityManager;
    private $cryptos = ['BTC', 'ETH'];
    private $fiats = ['GBP'];

    /**
     * GetRatesCommand constructor.
     * ==
     * @param ContainerInterface $container
     */
    public function __construct(
        ContainerInterface $container
    ) {
        parent::__construct();
        $this->entityManager = $container->get('doctrine')->getManager();
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
        $this->getFiats();
    }

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
            $data = $this->listExchangeRates($this->client, $crypto);
            $gbp = $data['rates']['GBP'];

            $r = new Rates();
            $r->setCurrency($crypto);
            $r->setCurrency2('GBP');
            $r->setValue($gbp);
            $r->setDateAdd(new \DateTime());

            $this->entityManager->persist($r);
            $this->entityManager->flush();
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
}
