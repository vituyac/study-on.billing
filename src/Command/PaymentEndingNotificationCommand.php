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

#[AsCommand(name: 'payment:ending:notification')]
class PaymentEndingNotificationCommand extends Command
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
        $from = new \DateTimeImmutable('tomorrow 00:00:00');
        $to = new \DateTimeImmutable('tomorrow 23:59:59');

        $transactions = $this->transactionRepository->findRentEndingBetween($from, $to);

        $usersCourses = [];

        foreach ($transactions as $transaction) {
            $user = $transaction->getCustomer();
            $course = $transaction->getCourse();

            $usersCourses[$user->getEmail()][] = [
                'courseTitle' => $course->getTitle(),
                'expiresAt' => $transaction->getExpiresAt(),
            ];
        }

        foreach ($usersCourses as $email => $courses) {
            $html = $this->twig->render('reports/rent_ending_notification.html.twig', [
                'courses' => $courses,
            ]);

            $message = (new Email())
                ->from('study-on@example.com')
                ->to($email)
                ->subject('Срок аренды курсов подходит к концу')
                ->html($html);

            $this->mailer->send($message);
        }

        $output->writeln(sprintf('Отправлено уведомлений: %d', count($usersCourses)));

        return Command::SUCCESS;
    }
}
