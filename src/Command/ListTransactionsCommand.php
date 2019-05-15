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

class ListTransactionsCommand extends Command
{
    protected static $defaultName = 'zoinbase:list:transactions';

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

        $this->listTransactions();
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

    public function listTransactions()
    {
        //$accounts = $this->client->getAccounts();
        $account = $this->client->getPrimaryAccount();
        $transactions = $this->client->getAccountTransactions($account);
        $this->d($transactions);
    }
}
