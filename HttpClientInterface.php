<?php 
interface HttpClientInterface
{
    public function post(string $url, array $data, array $headers = []): array;
    public function get(string $url, array $params = [], array $headers = []): array;
}