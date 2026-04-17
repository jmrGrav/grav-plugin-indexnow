<?php
namespace Grav\Plugin;

use Grav\Common\Plugin;
use Grav\Common\Page\Interfaces\PageInterface;
use RocketTheme\Toolbox\Event\Event;

class IndexnowPlugin extends Plugin
{
    // Pages exclues — home FR, home EN, politique de confidentialité
    const EXCLUDED_ROUTES = ['/', '/fr', '/en', '/privacy-policies'];

    public static function getSubscribedEvents(): array
    {
        return [
            'onAdminAfterSave' => ['onAdminAfterSave', 0],
            'onMcpAfterSave'   => ['onMcpAfterSave', 0],
        ];
    }

    /**
     * Déclenché après chaque sauvegarde réussie dans l'admin Grav.
     * Fonctionne pour les pages classiques ET les Flex objects.
     */
    public function onAdminAfterSave(Event $event): void
    {
        $object = $event['page'] ?? $event['object'] ?? null;

        if (!$object) {
            return;
        }

        // Ignorer les non-pages (users, config…)
        if (!($object instanceof PageInterface)) {
            return;
        }

        // Ignorer les pages non publiées
        if (method_exists($object, 'published') && !$object->published()) {
            return;
        }

        // Récupérer la route (pages classiques et Flex)
        if (method_exists($object, 'route')) {
            $route = $object->route();
        } elseif (method_exists($object, 'getRoute')) {
            $route = $object->getRoute();
        } else {
            return;
        }

        if (!$route) {
            return;
        }

        // Ignorer les pages exclues et les tags
        if (in_array($route, self::EXCLUDED_ROUTES, true)) {
            return;
        }
        if (str_starts_with($route, '/tag')) {
            return;
        }

        $this->submitToIndexNow($route);
    }

    /**
     * Déclenché après create_page / update_page via le plugin MCP.
     */
    public function onMcpAfterSave(Event $event): void
    {
        $route = $event['route'] ?? null;
        if (!$route) return;

        if (in_array($route, self::EXCLUDED_ROUTES, true)) return;
        if (str_starts_with($route, '/tag')) return;

        $this->submitToIndexNow($route);
    }

    /**
     * Soumet toutes les URLs de contenu à IndexNow (FR + EN).
     * Appelé par le hook onAdminAfterSave pour la page modifiée uniquement.
     */
    private function submitToIndexNow(string $route): void
    {
        $config   = $this->grav['config'];
        $key      = $config->get('plugins.indexnow.key', '');
        $host     = $config->get('plugins.indexnow.host', 'arleo.eu');
        $keyFile  = $config->get('plugins.indexnow.key_file', "https://{$host}/{$key}.txt");

        if (empty($key)) {
            $this->grav['log']->warning('[IndexNow] Clé API manquante dans la configuration du plugin.');
            return;
        }

        // Construire toutes les URLs à soumettre (FR + EN si dispo)
        $baseUrl = "https://{$host}";
        $urls    = [];

        // Version FR
        $urls[] = $baseUrl . '/fr' . $route;
        // Version EN
        $urls[] = $baseUrl . '/en' . $route;
        // URL canonique sans préfixe de langue
        $urls[] = $baseUrl . $route;

        // Dédoublonnage
        $urls = array_unique($urls);

        $payload = json_encode([
            'host'        => $host,
            'key'         => $key,
            'keyLocation' => $keyFile,
            'urlList'     => array_values($urls),
        ]);

        $ch = curl_init('https://api.indexnow.org/indexnow');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json; charset=utf-8'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            $this->grav['log']->error("[IndexNow] Erreur cURL : {$error}");
            return;
        }

        if ($httpCode === 200 || $httpCode === 202) {
            $this->grav['log']->info("[IndexNow] ✅ Soumission OK (HTTP {$httpCode}) pour : " . implode(', ', $urls));
        } else {
            $this->grav['log']->warning("[IndexNow] ⚠️ Soumission HTTP {$httpCode} pour : " . implode(', ', $urls) . " — réponse : {$response}");
        }
    }
}
