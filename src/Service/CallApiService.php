<?php
namespace App\Service;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\Request;
 
class CallApiService{

    private $client;
    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    } 

    public function getRanking($id){
    
        $curl = curl_init();
        $params = array('league' => $id, 'season' => 2022);
        $url = 'https://v3.football.api-sports.io/standings?'. http_build_query($params);
        curl_setopt_array($curl, array(
          CURLOPT_URL => $url,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'GET',
          CURLOPT_HTTPHEADER => array(
            'x-rapidapi-key: '.$_ENV["APi_KEY_SPORT"],
            'x-rapidapi-host: v3.football.api-sports.io'
          ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        return  $response;
    }

    public function getLive($id){
    
        $curl = curl_init();
        $params = array('league' => $id, 'season' => 2022);
        $url = 'https://v3.football.api-sports.io/standings?'. http_build_query($params);
        curl_setopt_array($curl, array(
          CURLOPT_URL => $url,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'GET',
          CURLOPT_HTTPHEADER => array(
            'x-rapidapi-key:'.$_ENV["APi_KEY_SPORT"],
            'x-rapidapi-host: v3.football.api-sports.io'
          ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        return  $response;
    }
}