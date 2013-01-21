<?php
/*
 * This file is part of the Voycer confluence xml-rpc backup.
 *
 * (c) Voycer AG <info@voycer.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Voycer\Confluence\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Nino Wagensonner <n.wagensonner@voycer.om>
 * @author Robert Gurber <r.gruber@voycer.om>
 */
class PdfExportCommand extends Command
{
    /**
     * Configures the command
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('confluence:backup')
            ->setDescription('Export a confluence space as a pdf file and store it.')
            ->addArgument(
                'url',
                InputArgument::REQUIRED,
                'The url of the xml-rpc server of confluence'
            )
            ->addArgument(
                'space',
                InputArgument::REQUIRED,
                'The name of the space to be exported'
            )
            ->addArgument(
                'export-path',
                InputArgument::REQUIRED,
                'The optional path to store the created export file'
            )
            ->addOption(
                'username',
                null,
                InputArgument::REQUIRED,
                'The username to access confluence'
            )
            ->addOption(
                'password',
                null,
                InputArgument::OPTIONAL,
                'The password to access confluence'
            )
        ;
    }

    /**
     * Executes the current command.
     *
     * This method is not abstract because you can use this class
     * as a concrete class. In this case, instead of defining the
     * execute() method, you set the code to execute by passing
     * a Closure to the setCode() method.
     *
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     *
     * @return null|integer null or 0 if everything went fine, or an error code
     *
     * @throws \LogicException When this abstract method is not implemented
     * @see    setCode()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $url = $input->getArgument('url');
        $space = $input->getArgument('space');
        $exportPath = $input->getArgument('export-path');
        $username = $input->getOption('username');
        $password = $input->getOption('password');

        if ($password === null) {
            $dialog = $this->getHelper('dialog');
            $password = $dialog->askHiddenResponse($output, '<question>What is your confluence password?</question>');
        }


        $xmlRcpClient = new \Zend_XmlRpc_Client($url);
        $token = $xmlRcpClient->call('pdfexport.login', array('username' => $username, 'password' => $password));

        if ($token) {
            $downloadUrl = $xmlRcpClient->call('pdfexport.exportSpace', array('token' => $token, 'spaceKey' => $space));
        } else {
            $output->writeln("<error>Login failed!</error>");

            return -1;
        }

        $output->writeln("<info>Downloading generated pdf from '{$downloadUrl}'</info>");

        $client = new \Zend_Http_Client($downloadUrl);
        $client->setCookieJar(new \Zend_Http_CookieJar());
        $client->setAuth($username, $password);
        $response = $client->request();
        file_put_contents($exportPath, $response->getBody());

        $output->writeln("<info>The exported pdf file was successfully written to '{$exportPath}'</info>");

        return 0;
    }
}

