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
class ReleaseUpdateCommand extends Command
{
    /**
     * Configures the command
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('confluence:release-notes')
            ->setDescription('stores release notes of a given project in confluence.')
            ->addArgument(
                'url',
                InputArgument::REQUIRED,
                'The url of the xml-rpc server of confluence'
            )
            ->addArgument(
                'pageId',
                InputArgument::REQUIRED,
                'The name of the page to be used'
            )
            ->addArgument(
                'comment',
                InputArgument::OPTIONAL,
                'comment'
            )
            ->addOption('username', null, InputOption::VALUE_REQUIRED,
                'The username to access confluence'
            )
            ->addOption(
                'password',
                null,
                InputOption::VALUE_OPTIONAL,
                'The password to access confluence'
            )
        ;
    }

    /**
     * returns content from the stdin
     *
     * @return string
     */
    protected function getStdin()
    {
        stream_set_blocking(STDIN, 0);

        $result = '';

        while (($content = fgets(STDIN, 24 * 1024 * 1024)))
        {
            $result .= $content;
        }

        return $result;
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
        $stdin = $this->getStdin();

        if (trim($stdin) === '') {
            $output->writeln("<error>No content provided! Please pipe output to this script.</error>");
            return -1;
        }

        $url = $input->getArgument('url');
        $pageId = $input->getArgument('pageId');
        $username = $input->getOption('username');
        $password = $input->getOption('password');
        $comment = $input->getArgument('comment');

        if ($password === null) {
            $dialog = $this->getHelper('dialog');
            $password = $dialog->askHiddenResponse($output, '<question>What is your confluence password?</question>');
        }


        $xmlRcpClient = new \Zend_XmlRpc_Client($url);
        $token = $xmlRcpClient->call('confluence1.login', array('username' => $username, 'password' => $password));

        $pageUpdateOptions =   array(
            'versionComment' => $comment,
            'minorEdit' => true
        );

        if ($token) {
            $page =   $downloadUrl = $xmlRcpClient->call(
                'confluence1.getPage',
                array('token' => $token, 'pageId' => $pageId)
            );

            if (! is_array($page) || $page['id'] !== $pageId) {
                $output->writeln("<error>Page not found!</error>");
                return -1;
            }

            $page['content'] = $stdin;

            $result = $xmlRcpClient->call(
                'confluence1.updatePage',
                array('token' => $token, 'page' => $page, 'pageUpdateOptions' => $pageUpdateOptions)
            );
        } else {
            $output->writeln("<error>Login failed!</error>");

            return -1;
        }

        if (is_array($result)) {
            $output->writeln("<info>update successful.</info>");
        }

        return 0;
    }
}

