<?php
/** Modern Joomla 5/6 captcha provider based on RJCreations QA (GPL-3.0-or-later). */
namespace RJCreations\Plugin\Captcha\Qa\Provider;

defined('_JEXEC') or die;

use Joomla\CMS\Captcha\CaptchaProviderInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;

final class QaCaptchaProvider implements CaptchaProviderInterface
{
    private array $qandas = [];
    private bool $loaded = false;

    public function __construct(private Registry $params) {}

    public function getName(): string { return 'qa'; }

    public function setupField(FormField $field, \SimpleXMLElement $element): void
    {
        $element['hiddenLabel'] = 'true';
    }

    private function loadQuestions(): void
    {
        if ($this->loaded) return;
        $this->loaded = true;
        $language = Factory::getApplication()->getLanguage();
        $language->load('plg_captcha_qa', JPATH_ADMINISTRATOR, null, true);
        $language->load('custom', JPATH_PLUGINS . '/captcha/qa', $language->getTag(), true);
        $custom = (string) $this->params->get('customqa', '');
        if ($custom !== '' && $custom !== '0') {
            if (preg_match('/\A[a-zA-Z0-9][a-zA-Z0-9._-]{0,79}\.json\z/D', $custom) && !str_contains($custom, '..')) {
                $this->loadFile(JPATH_ROOT . '/media/plg_captcha_qa/custom/' . $custom);
            }
            return;
        }
        $lang = $language->getTag();
        $this->loadFile(JPATH_ROOT . '/media/plg_captcha_qa/qalang/qandas_' . $lang . '.json');
        if (!$this->qandas) $this->loadFile(JPATH_ROOT . '/media/plg_captcha_qa/qalang/qandas_en-GB.json');
    }

    private function loadFile(string $path): void
    {
        if (!is_file($path)) return;
        $qas = json_decode((string) file_get_contents($path), true);
        if (!is_array($qas)) return;
        foreach ($qas as $qa) {
            if (!is_array($qa)) continue;
            foreach ($qa as $question => $answers) {
                if (is_string($question) && is_array($answers) && $answers) {
                    $this->qandas[] = [$question => array_values(array_filter($answers, 'is_string'))];
                }
            }
        }
    }

    private function secret(): string
    {
        $session = Factory::getApplication()->getSession();
        $secret = $session->get('qa_captcha_secret', '', 'plg_captcha_qa');
        if (!is_string($secret) || strlen($secret) < 32) {
            $secret = bin2hex(random_bytes(32));
            $session->set('qa_captcha_secret', $secret, 'plg_captcha_qa');
        }
        return $secret;
    }

    public function display(string $name = '', array $attributes = []): string
    {
        $this->loadQuestions();
        if (!$this->qandas) return '';
        // Keep challenges short-lived and reject a previously accepted challenge.
        $index = random_int(0, count($this->qandas) - 1);
        $timestamp = time();
        $nonce = bin2hex(random_bytes(8));
        $payload = $index . ':' . $timestamp . ':' . $nonce;
        $mac = hash_hmac('sha256', $payload, $this->secret());
        $token = $payload . ':' . $mac;
        $question = (string) array_key_first($this->qandas[$index]);
        $id = (string) ($attributes['id'] ?? 'qa-captcha');
        $class = (string) ($attributes['class'] ?? '');
        $esc = static fn ($s) => htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        return '<span>' . $esc(Text::_('PLG_CAPTCHA_QA_LABEL_PLEASE')) . '</span><br>' . $esc($question)
            . '<br><input type="text" class="form-control ' . $esc($class) . '" id="' . $esc($id)
            . '" name="' . $esc($name) . '" required="required" aria-required="true" autocomplete="off" value="">'
            . '<input type="hidden" name="captcha_code" value="' . $esc($token) . '">';
    }

    public function checkAnswer(?string $code = null): bool
    {
        $this->loadQuestions();
        $app = Factory::getApplication();
        $token = $app->getInput()->post->getString('captcha_code', '');
        $parts = explode(':', $token);
        if (count($parts) !== 4 || !ctype_digit($parts[0]) || !ctype_digit($parts[1])
            || !preg_match('/\A[a-f0-9]{16}\z/D', $parts[2]) || !preg_match('/\A[a-f0-9]{64}\z/D', $parts[3])) {
            return $this->error('PLG_CAPTCHA_QA_ERROR_GENERAL');
        }
        [$idx, $stamp, $nonce, $mac] = $parts;
        $payload = $idx . ':' . $stamp . ':' . $nonce;
        if (!hash_equals(hash_hmac('sha256', $payload, $this->secret()), $mac)) {
            return $this->error('PLG_CAPTCHA_QA_ERROR_GENERAL');
        }
        $age = time() - (int) $stamp;
        if ($age < 0 || $age > 3600 || !isset($this->qandas[(int) $idx])) {
            return $this->error('PLG_CAPTCHA_QA_ERROR_GENERAL');
        }
        // A signed token alone prevents tampering, but does not prevent replay.
        // Store accepted challenges in the existing Joomla session.
        $session = $app->getSession();
        $used = $session->get('qa_captcha_used', [], 'plg_captcha_qa');
        if (!is_array($used)) $used = [];
        $used = array_filter($used, static fn ($expiry) => is_int($expiry) && $expiry > time());
        if (isset($used[$nonce])) return $this->error('PLG_CAPTCHA_QA_ERROR_GENERAL');
        $answers = array_values($this->qandas[(int) $idx])[0];
        if (!in_array(trim((string) $code), array_map('trim', $answers), true)) {
            return $this->error('PLG_CAPTCHA_QA_ERROR_NOT_HUMAN', 'PLG_CAPTCHA_QA_ERROR_INCORRECT');
        }
        // Check the minimum delay only after verifying the answer. Incorrect
        // answers should report an answer error, not a misleading timing error.
        if ($age < max(0, (int) $this->params->get('time_check', 10))) {
            return $this->error('PLG_CAPTCHA_QA_ERROR_NOT_HUMAN', 'PLG_CAPTCHA_QA_ERROR_TOO_QUICK');
        }
        // Session-scoped cooldown after a previous successful CAPTCHA check.
        // No IP tracking, fingerprinting or persistent database storage.
        $cooldown = max(0, (int) $this->params->get('success_cooldown', 300));
        $lastSuccess = (int) $session->get('qa_last_success', 0, 'plg_captcha_qa');
        $remaining = $cooldown - (time() - $lastSuccess);
        if ($cooldown > 0 && $lastSuccess > 0 && $remaining > 0) {
            return $this->error('PLG_CAPTCHA_QA_ERROR_NOT_HUMAN', 'PLG_CAPTCHA_QA_ERROR_COOLDOWN', $remaining);
        }
        $used[$nonce] = time() + 3600;
        $session->set('qa_captcha_used', $used, 'plg_captcha_qa');
        $session->set('qa_last_success', time(), 'plg_captcha_qa');
        return true;
    }

    private function error(string $message, ?string $detail = null, ?int $seconds = null): bool
    {
        $detailText = $detail === null ? '' : ' ' . ($seconds === null ? Text::_($detail) : Text::sprintf($detail, $seconds));
        Factory::getApplication()->enqueueMessage(Text::_($message) . $detailText, 'error');
        return false;
    }
}
