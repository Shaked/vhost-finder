<?php

namespace App;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use shaked\time\Duration;
use shaked\time\Sleep;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class VHostFinderCommand extends Command {

    public function configure() {
        $this->setName('vhost:finder');
        $this
            ->addOption('host', null, InputOption::VALUE_REQUIRED, 'Host')
            ->addOption('ip', null, InputOption::VALUE_REQUIRED, 'Ip')
            ->addOption('wordlist', null, InputOption::VALUE_REQUIRED, 'Virtual host word list')
            ->addOption('headers', null, InputOption::VALUE_REQUIRED, 'Headers file')
            ->addOption('httpMethod', 'm', InputOption::VALUE_OPTIONAL, 'HTTP Method: HEAD, GET, POST, PUT, DELETE, OPTIONS', 'HEAD')
            ->addOption('ssl', null, InputOption::VALUE_OPTIONAL, 'Use SSL', true)
            ->addOption('proxies', null, InputOption::VALUE_OPTIONAL, 'Proxy list file', null)
            ->addOption('sleepBetweenRequests', null, InputOption::VALUE_OPTIONAL, 'Sleep between each request (milliseconds)', null);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        $ip       = $input->getOption('ip');
        $host     = $input->getOption('host');
        $wordlist = $input->getOption('wordlist');

        if (!file_exists($wordlist)) {
            throw new \Exception('No such file ' . $wordlist);
        }

        $userAgents = explodeNoEmpty("\n", file_get_contents(__DIR__ . '/../user-agents.txt'));

        $virtualHosts = explodeNoEmpty("\n", file_get_contents($wordlist));
        $headers      = [];
        if ($input->getOption('headers')) {
            $headers = json_decode(file_get_contents($input->getOption('headers')), true);
        }

        $scheme = $input->getOption('ssl') ? 'https://' : 'http://';

        $client = new Client([
            'base_uri' => $scheme . $ip,
            'timeout'  => 2.0,
            'verify'   => false,
            'debug'    => $output->isVeryVerbose(),
        ]);

        $results = [];
        foreach ($virtualHosts as $virtualHost) {
            $fullHost = $virtualHost . '.' . $host;
            $message  = '';
            try {
                $extra = [];
                if ($input->getOption('proxies')) {
                    $proxies        = file_get_contents($input->getOption('proxies'));
                    $proxies        = explodeNoEmpty("\n", $proxies);
                    $proxy          = $proxies[array_rand($proxies)];
                    $extra['proxy'] = $proxy;
                }
                $userAgent = $userAgents[array_rand($userAgents)];
                $options   = array_merge([
                    'headers' => array_merge([
                        'User-Agent' => $userAgent,
                        'Host'       => $fullHost,
                    ], $headers),
                ], $extra);

                $ret = $client->request($input->getOption('httpMethod'), '/', $options);
                if ($output->isVerbose()) {
                    $message = 'Found: ' . $fullHost . ', ' . $ret->getStatusCode();
                    if ($output->isVeryVerbose()) {
                        $message .= PHP_EOL . ' Content: ' . (string) $ret->getBody()->getContents() . PHP_EOL . 'Headers: ' . var_export($ret->getHeaders(), true);
                    }
                }
                $result = [
                    'statusCode' => $ret->getStatusCode(),
                ];
            } catch (RequestException $e) {
                if ($output->isVerbose()) {
                    if ($e->hasResponse()) {
                        $message = 'Not found: ' . $fullHost . ', ' . $e->getResponse()->getStatusCode();
                    } else {
                        $message = 'Could not connect host. Maybe something wrong with your proxy?';
                    }
                }
                if ($output->isVeryVerbose()) {
                    $message .= PHP_EOL . 'Exception: ' . $e->getMessage();
                    if ($e->hasResponse()) {
                        $message .= PHP_EOL . 'Content: ' . $e->getResponse()->getBody()->getContents();
                        $message .= PHP_EOL . 'Headers: ' . var_export($e->getResponse()->getHeaders(), true);
                    }
                }

                if ($e->hasResponse()) {
                    $result = [
                        'statusCode' => $e->getResponse()->getStatusCode(),
                    ];
                } else {
                    $result = [
                        'statusCode' => -1,
                    ];
                }

            }

            if ($input->getOption('sleepBetweenRequests')) {
                $sleepBetweenRequests = $input->getOption('sleepBetweenRequests');
                $milliseconds         = Duration::millisecond($sleepBetweenRequests);
                if ($output->isVerbose()) {
                    $output->writeln('Sleeping for ' . $milliseconds);
                }
                (new Sleep())->for($milliseconds);
            }

            $output->writeln($message);
            $results[$fullHost] = $result;
        }
        $resultsString = json_encode($results);
        $output->writeln($resultsString);
    }
}