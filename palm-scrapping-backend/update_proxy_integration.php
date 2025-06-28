<?php
$file = 'app/Http/Services/ProductsServices.php';
$content = file_get_contents($file);

$newFetchPageMethod = '    private function fetchPage($url)
    {
        $userAgent = $this->userAgents[array_rand($this->userAgents)];
        
        $proxy = $this->getProxyFromService();
        
        $options = [
            "headers" => [
                "User-Agent" => $userAgent,
            ]
        ];
        
        if ($proxy) {
            $proxyUrl = $proxy["protocol"] . "://" . $proxy["host"] . ":" . $proxy["port"];
            if (!empty($proxy["username"]) && !empty($proxy["password"])) {
                $proxyUrl = $proxy["protocol"] . "://" . $proxy["username"] . ":" . $proxy["password"] . "@" . $proxy["host"] . ":" . $proxy["port"];
            }
            $options["proxy"] = $proxyUrl;
        }
        
        $response = $this->client->get($url, $options);
        return $response->getBody()->getContents();
    }

    private function getProxyFromService()
    {
        try {
            $response = $this->client->get("http://localhost:8080/proxy");
            $data = json_decode($response->getBody()->getContents(), true);
            
            if ($data["success"] && isset($data["data"])) {
                return $data["data"];
            }
        } catch (\Exception $e) {
            Log::warning("Failed to get proxy from service: " . $e->getMessage());
        }
        
        return null;
    }';

$pattern = '/private function fetchPage\(\$url\)\s*\{[^}]*\}/s';
$replacement = $newFetchPageMethod;

$updatedContent = preg_replace($pattern, $replacement, $content);
file_put_contents($file, $updatedContent);

echo "Proxy integration updated successfully!\n";
