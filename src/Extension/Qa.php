<?php
/** Modern Joomla 5/6 Q&A Captcha plugin; based on RJCreations QA 1.5.5 (GPL-3.0-or-later). */
namespace RJCreations\Plugin\Captcha\Qa\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Event\Captcha\CaptchaSetupEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Session\Session;
use Joomla\Event\EventInterface;
use Joomla\Event\SubscriberInterface;
use RJCreations\Plugin\Captcha\Qa\Provider\QaCaptchaProvider;

final class Qa extends CMSPlugin implements SubscriberInterface
{
    protected $autoloadLanguage = true;

    public static function getSubscribedEvents(): array
    {
        return ['onCaptchaSetup' => 'onCaptchaSetup', 'onAjaxQa' => 'onAjaxQa'];
    }

    public function onCaptchaSetup(CaptchaSetupEvent $event): void
    {
        $event->getCaptchaRegistry()->add(new QaCaptchaProvider($this->params));
    }

    /** This AJAX handler is restricted to authorized backend administrators. */
    public function onAjaxQa(EventInterface $event): void
    {
        $app = $this->getApplication();
        if (!$app->isClient('administrator') || !$app->getIdentity()->authorise('core.manage', 'com_plugins')) {
            throw new \RuntimeException('Not authorized', 403);
        }
        $input = $app->getInput();
        $post = $input->getMethod() === 'POST';
        if ($post && !Session::checkToken('post')) {
            throw new \RuntimeException('Invalid session token', 403);
        }
        if ($post) {
            $data = $input->post->getArray();
            $filename = (string) ($data['FN'] ?? '');
            if (!preg_match('/\A[a-zA-Z0-9][a-zA-Z0-9._-]{0,79}\.json\z/D', $filename) || str_contains($filename, '..')) {
                throw new \RuntimeException('Invalid filename', 400);
            }
            $questions = $data['Q'] ?? [];
            $answers = $data['A'] ?? [];
            if (!is_array($questions) || !is_array($answers) || count($questions) !== count($answers) || count($questions) > 200) {
                throw new \RuntimeException('Invalid question data', 400);
            }
            $qas = [];
            foreach ($questions as $i => $question) {
                if (!is_string($question) || !isset($answers[$i]) || !is_string($answers[$i])) {
                    throw new \RuntimeException('Invalid question data', 400);
                }
                $question = trim($question);
                $opts = array_values(array_filter(array_map('trim', explode('|', $answers[$i])), static fn ($x) => $x !== ''));
                if ($question === '' || !$opts || mb_strlen($question) > 500 || strlen($answers[$i]) > 2000) {
                    throw new \RuntimeException('Question and answers required', 400);
                }
                $qas[] = [$question => $opts];
            }
            if (!$qas) {
                throw new \RuntimeException('At least one question required', 400);
            }
            $directory = JPATH_ROOT . '/media/plg_captcha_qa/custom';
            if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
                throw new \RuntimeException('Could not create directory', 500);
            }
            $json = json_encode($qas, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
            if (is_link($directory . '/' . $filename) || (is_file($directory . '/' . $filename) && !is_writable($directory . '/' . $filename))) {
                throw new \RuntimeException('Invalid destination', 400);
            }
            if (file_put_contents($directory . '/' . $filename, $json, LOCK_EX) === false) {
                throw new \RuntimeException('Could not save questions', 500);
            }
            $app->setHeader('Content-Type', 'application/json; charset=utf-8', true);
            echo json_encode(['success' => true]);
        } else {
            $filename = $input->getString('itemid', '');
            $questions = ['' => ''];
            if (preg_match('/\A[a-zA-Z0-9][a-zA-Z0-9._-]{0,79}\.json\z/D', $filename) && !str_contains($filename, '..')) {
                $path = JPATH_ROOT . '/media/plg_captcha_qa/custom/' . $filename;
                if (is_file($path)) {
                    $qas = json_decode((string) file_get_contents($path), true);
                    if (is_array($qas)) {
                        $questions = [];
                        foreach ($qas as $qa) {
                            if (!is_array($qa)) continue;
                            foreach ($qa as $q => $a) {
                                if (is_array($a)) $questions[$q] = implode(' | ', $a);
                            }
                        }
                    }
                }
            }
            echo (new FileLayout('modal.questions', JPATH_PLUGINS . '/captcha/qa/layouts'))
                ->render(['questions' => $questions, 'customnum' => $filename, 'csrfToken' => Session::getFormToken()]);
        }
        $app->close();
    }
}
