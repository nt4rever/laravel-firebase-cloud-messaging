<?php

namespace App\Services;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Exception\Messaging as MessagingErrors;
use Kreait\Firebase\Exception\MessagingException;

class CloudMessagingService
{
    public function __construct(private Messaging $messaging)
    {

    }

    public function sendToTopic($topic)
    {
        $message = CloudMessage::withTarget('topic', $topic)
            ->withNotification([
                'title' => 'Test',
                'body' => 'Test',
            ])
            ->withData([
                'id' => '1000',
            ]);

        try {
            $result = $this->messaging->send($message);
            // $result = ['name' => 'projects/<project-id>/messages/6810356097230477954']

            return $result;
        } catch (MessagingException $e) {
            logger()->error(get_called_class(), [$e]);
        }

        try {
            $this->messaging->send($message);
        } catch (MessagingErrors\NotFound $e) {
            echo 'The target device could not be found.';
        } catch (MessagingErrors\InvalidMessage $e) {
            echo 'The given message is malformatted.';
        } catch (MessagingErrors\ServerUnavailable $e) {
            $retryAfter = $e->retryAfter();

            echo 'The FCM servers are currently unavailable. Retrying at ' . $retryAfter->format(\DATE_ATOM);

            // This is just an example. Using `sleep()` will block your script execution, don't do this.
            while ($retryAfter <= new \DateTimeImmutable()) {
                sleep(1);
            }

            $this->messaging->send($message);
        } catch (MessagingErrors\ServerError $e) {
            echo 'The FCM servers are down.';
        } catch (MessagingException $e) {
            // Fallback handling
            echo 'Unable to send message: ' . $e->getMessage();
        }
    }
}
