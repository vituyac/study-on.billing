<?php

namespace App\Command;

use App\Repository\TransactionRepository;
use App\Service\Twig;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[AsCommand(name: 'payment:report')]
class PaymentReportCommand extends Command
{
    public function __construct(
        private TransactionRepository $transactionRepository,
        private MailerInterface $mailer,
        private Twig $twig,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $from = new \DateTimeImmutable('first day of this month 00:00:00');
        $to = new \DateTimeImmutable('last day of this month 23:59:59');

        $reportRows = $this->transactionRepository->getPaidCoursesReport($from, $to);

        $total = 0;

        foreach ($reportRows as $row) {
            $total += $row['totalAmount'];
        }

        $html = $this->twig->render('reports/payment_report.html.twig', [
            'from' => $from,
            'to' => $to,
            'rows' => $reportRows,
            'total' => $total,
        ]);

        $message = (new Email())
            ->from('study-on@example.com')
            ->to('study-on@example.com')
            ->subject('Отчёт об оплаченных курсах за месяц')
            ->html($html);

        $this->mailer->send($message);

        $output->writeln('Отчёт отправлен на почту.');

        return Command::SUCCESS;
    }
}
