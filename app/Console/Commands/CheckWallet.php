<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Client;

class checkWallet extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'elrond:checkWallet {address?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check the total amount of LKMEX and MEX and EGLD from an wallet add';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {


        $addressToCheck = $this->argument('address');

        $requestEGLDPRICE = (new Client())->request('get','https://api.elrond.com/economics');
        $egldPrice = json_decode($requestEGLDPRICE->getBody())->price;

        $requestMEXPRICE = (new Client())->request('get','https://api.elrond.com/mex-economics');
        $mexPrice = json_decode($requestMEXPRICE->getBody())->price;

        $jsonResponse = $this->getResponseJson("https://api.elrond.com/accounts/$addressToCheck/nfts?type=MetaESDT");


        $r = (new Client())->request('POST', 'https://graph.maiar.exchange/graphql', [
            'body' => '{"query":"query { pairs { firstToken { name }, firstTokenPriceUSD, secondToken { name }, secondTokenPriceUSD, liquidityPoolToken { name, supply, decimals },liquidityPoolTokenPriceUSD }}"}',
            'headers' => [
                'Content-Type' => 'application/json',
            ]
        ]);

        $responseToParse =json_decode($r->getBody())->data->pairs;

        $priceUsdcEgldLP = $responseToParse[1]->liquidityPoolTokenPriceUSD;

        $priceEgldMexLP = $responseToParse[0]->liquidityPoolTokenPriceUSD;

        $this->alert("Price per LP : $priceEgldMexLP");


        $mexAmountTotalLocked = 0;
        $lpAmountTotalLocked = 0;
        $lpAmountTotal = 0;
        $lpAmountTotalUSDC = 0;

        foreach ($jsonResponse as $objectBlock){
            $lpAmount = 0;
            $mexAmountLocked = 0;
            $lpAmountLocked = 0;
            $usdcAmountStaked = 0;

            $balance = $objectBlock->balance;
            $decimals = $objectBlock->decimals;
            $name = $objectBlock->name;
            $identifier= $objectBlock->collection;

            $balance18s = $balance/(10**$decimals);

            /* Ignore lower balances */
            if (!($balance18s < 0.000001)){
                $this->comment($name ." - " . $balance18s  ." - " .$identifier);
            }

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


            /**
             * USDC LP
             */
            if($name == 'EGLDUSDCLPStaked'){
                $usdcAmountStaked = $balance18s/12;
            }
            $lpAmountTotalUSDC = $lpAmountTotalUSDC + $usdcAmountStaked;


        }

        dd($lpAmountTotalUSDC * $priceUsdcEgldLP);

        $this->line("LPUnlocked: $lpAmountTotal");
        $this->line("LPLocked: $lpAmountTotalLocked");
        $this->line("MEXLocked: $mexAmountTotalLocked");


        /**
         * CALCULATE AMOUNT OF LP
         */

        $totalUSDperMEX = ($lpAmountTotal/2)*$priceEgldMexLP;
        $totalUSDperMEXLocked = ($lpAmountTotalLocked/2)*$priceEgldMexLP;
        $totalUSDperEGLD = ($lpAmountTotal/2)*$priceEgldMexLP;
        $totalUSDperEGLDLocked = ($lpAmountTotalLocked/2)*$priceEgldMexLP;

        $this->line("---------------------------------");
        $this->line("MEX-USD: $totalUSDperMEX");
        $this->line("EGLD-USD: $totalUSDperEGLD");
        $this->line("---------------------------------");
        $this->line("MEXLocked-USD: $totalUSDperMEXLocked");
        $this->line("EGLDLocked-USD: $totalUSDperEGLDLocked");

        $this->alert($totalUSDperEGLD+$totalUSDperMEX+$totalUSDperEGLDLocked+$totalUSDperMEXLocked);

        $amountEGLD = ($totalUSDperEGLD+$totalUSDperEGLDLocked)/$egldPrice;
        $amountMex = $totalUSDperMEX/$mexPrice;
        $amountMexLocked = $totalUSDperMEXLocked/$mexPrice;
        $amountEGLDEquivalentMex = $totalUSDperMEX/$egldPrice;
        $amountEGLDEquivalentLKMex = $totalUSDperMEXLocked/$egldPrice;

        $this->line("---------------------------------");

        $this->line("EGLD: $amountEGLD");
        $this->line("MEX: $amountMex - equivalent of $amountEGLDEquivalentMex EGLD");
        $this->line("LKMEX: $amountMexLocked - equivalent of $amountEGLDEquivalentLKMex EGLD");

        $totalALLEGLD = $amountEGLD+$amountEGLDEquivalentMex+$amountEGLDEquivalentLKMex;
        $this->error("TOTAL EGLD OF ACCOUNT = $totalALLEGLD");

        return 0;
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



