<?php namespace Core\Service;

use Zend\Mail\Message;
use Zend\Mail\Transport\Sendmail;
use Zend\Mime;

class Mail
{
    /** @var $message Message */
    protected $message;

    public function __construct()
    {
        $this->message = new Message;
        $this->message->setEncoding('UTF-8');
        $this->from();
    }

    public function &getMessage()
    {
        return $this->message;
    }

    public function body($body, $is_html = true)
    {
        if ($is_html) {
            $html = new Mime\Part($body);
            $html->type = "text/html";

            $body = new Mime\Message;
            $body->setParts(array($html));

            $this->message->setBody($body);
        } else {
            $this->message->setBody($body);
        }

        return $this;
    }

    public function from($email = null, $name = null)
    {
        $this->message->setFrom($email ? $email : 'noreply@'.$_SERVER['HTTP_HOST'], $name ? $name : 'Robot');
        return $this;
    }

    public function subject($subject)
    {
        $this->message->setSubject($subject);
        return $this;
    }

    public function send($email)
    {
        $this->message->setTo($email);

        $transport = new Sendmail();
        $transport->send($this->message);
        return $this;
    }
}