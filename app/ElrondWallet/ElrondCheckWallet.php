<?php


namespace App\ElrondWallet;


use GuzzleHttp\Client;

class ElrondCheckWallet
{
    /**
     * @var
     */
    public $wallet;

    /**
     * ElrondCheckWallet constructor.
     * @param $wallet
     */
    public function __construct($wallet)
    {
        $this->wallet = $wallet;
    }


    public function getAllInformationsByAddress(){

        $wallet = $this->wallet;

        $requestEGLDPRICE = (new Client())->request('get','https://api.elrond.com/economics');
        $egldPrice = json_decode($requestEGLDPRICE->getBody())->price;

        $requestMEXPRICE = (new Client())->request('get','https://api.elrond.com/mex-economics');
        $mexPrice = json_decode($requestMEXPRICE->getBody())->price;

        $jsonResponse = $this->getResponseJson("https://api.elrond.com/accounts/$wallet/nfts?type=MetaESDT");

        $r = (new Client())->request('POST', 'https://graph.maiar.exchange/graphql', [
            'body' => '{"query":"query { pairs { firstToken { name }, firstTokenPriceUSD, secondToken { name }, secondTokenPriceUSD, liquidityPoolToken { name, supply, decimals },liquidityPoolTokenPriceUSD }}"}',
            'headers' => [
                'Content-Type' => 'application/json',
            ]
        ]);

        $responseToParse =json_decode($r->getBody())->data->pairs;
        $priceEgldMexLP = $responseToParse[0]->liquidityPoolTokenPriceUSD;

        $mexAmountTotalLocked = 0;
        $lpAmountTotalLocked = 0;
        $lpAmountTotal = 0;

        foreach ($jsonResponse as $objectBlock){
            $lpAmount = 0;
            $mexAmountLocked = 0;
            $lpAmountLocked = 0;

            $balance = $objectBlock->balance;
            $decimals = $objectBlock->decimals;
            $name = $objectBlock->name;
            $identifier= $objectBlock->collection;

            $balance18s = $balance/(10**$decimals);

            /**
             * LOCKED LP
             */
            if(str_contains($name, 'LockedLPStaked')){
                //check if value si greater than 100000
                if($balance18s > 100000){
                    //case MEX
                    $mexAmountLocked = $balance18s/12;
                }else{
                    //case LP
                    $lpAmountLocked = $balance18s/15;
                }
            }
            $mexAmountTotalLocked = $mexAmountTotalLocked + $mexAmountLocked;
            $lpAmountTotalLocked = $lpAmountTotalLocked + $lpAmountLocked;

            /**
             * UNLOCKED LP
             */
            if(str_contains($name, 'EGLDMEXLPStaked')){
                $lpAmount = $balance18s/15;
            }
            $lpAmountTotal = $lpAmountTotal + $lpAmount;

        }

        /*
        $this->line("LPUnlocked: $lpAmountTotal");
        $this->line("LPLocked: $lpAmountTotalLocked");
        $this->line("MEXLocked: $mexAmountTotalLocked");
        */

        /**
         * CALCULATE AMOUNT OF LP
         */

        $totalUSDperMEX = ($lpAmountTotal/2)*$priceEgldMexLP;
        $totalUSDperMEXLocked = ($lpAmountTotalLocked/2)*$priceEgldMexLP;
        $totalUSDperEGLD = ($lpAmountTotal/2)*$priceEgldMexLP;
        $totalUSDperEGLDLocked = ($lpAmountTotalLocked/2)*$priceEgldMexLP;

        /*
        $this->line("---------------------------------");
        $this->line("MEX-USD: $totalUSDperMEX");
        $this->line("EGLD-USD: $totalUSDperEGLD");
        $this->line("---------------------------------");
        $this->line("MEXLocked-USD: $totalUSDperMEXLocked");
        $this->line("EGLDLocked-USD: $totalUSDperEGLDLocked");

        $this->alert($totalUSDperEGLD+$totalUSDperMEX+$totalUSDperEGLDLocked+$totalUSDperMEXLocked);
        */
        $amountEGLD = ($totalUSDperEGLD+$totalUSDperEGLDLocked)/$egldPrice;
        $amountMex = $totalUSDperMEX/$mexPrice;
        $amountMexLocked = $totalUSDperMEXLocked/$mexPrice;
        $amountEGLDEquivalentMex = $totalUSDperMEX/$egldPrice;
        $amountEGLDEquivalentLKMex = $totalUSDperMEXLocked/$egldPrice;

        /*
        $this->line("---------------------------------");

        $this->line("EGLD: $amountEGLD");
        $this->line("MEX: $amountMex - equivalent of $amountEGLDEquivalentMex EGLD");
        $this->line("LKMEX: $amountMexLocked - equivalent of $amountEGLDEquivalentLKMex EGLD");

        $this->error("TOTAL EGLD OF ACCOUNT = $totalALLEGLD");
        */

        $totalALLEGLD = $amountEGLD+$amountEGLDEquivalentMex+$amountEGLDEquivalentLKMex;

        $arrayWithInfo = [
            'amountEgld' => $amountEGLD,
            'amountMex' => $amountMex,
            'amountLKMex' => $amountMexLocked,
            'amountEquivalentInEgld' => $totalALLEGLD,
        ];

        return $arrayWithInfo;

    }


    /**
     * @param $apiUrl
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getResponseJson($apiUrl){
        $response = (new Client())->request('get',$apiUrl);
        $jsonResponse = json_decode($response->getBody());
        return $jsonResponse;
    }

    public function divideBy18($value){
        return $value/(1000000000000000000);
    }


}