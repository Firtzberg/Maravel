<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use \RestClient;
use \Excel;

class Marvel extends Command
{
    /**
     * Supported data types (keyes) and corresponding API routes (values).
     *
     * @var array
     */
    const TYPES = ['Comic' => 'comics', 'Story' => 'stories', 'Series' => 'series', 'Event' => 'events'];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'marvel {character : Name of the character} {type : Type of the data} {path? : path to file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetches data related to Marvel character and stores it into a file';

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
        // check validity of data type
        if (!array_key_exists($this->argument('type'), self::TYPES)){
            $this->error('Unknown data type "'.$this->argument('type').'", allowed are '.implode(', ', array_keys(self::TYPES)));
            return 1;
        }

        // get list of characters with given name
        $restClient = new RestClient();
        $response = $restClient->get("characters",
            $this->getParams(array('name' => $this->argument('character'), 'limit' => 2))
            )->getResponse();

        $json = json_decode($response->content());
        // handle failure
        if (!$restClient->isResponseStatusCode(200)) {
            $errorMessage = 'Failed to access MARVEL API.';
            if (property_exists($json, 'status')){
                $errorMessage .= ' Status:"'.$json->status.'"';
            }
            $this->error($errorMessage);
            return 3;
        }

        // check character existance
        $characterCount = sizeof($json->data->results);
        if ($characterCount == 0){
            $this->error('No characters with name '.$this->argument('character').' found');
            return 2;
        }
        else if ($characterCount > 1){
            $this->warn('More than 1 character found with name '.$this->argument('character'));
        }

        // get data for character in a bulk
        $characterId = $json->data->results[0]->id;
        $response = $restClient->get('characters/'.$characterId.'/'.self::TYPES[$this->argument('type')],
            $this->getParams(array('limit' => 40))
            )->getResponse();

        $json = json_decode($response->content());
        // handle failure
        if (!$restClient->isResponseStatusCode(200)) {
            $errorMessage = 'Failed to access MARVEL API.';
            if (property_exists($json, 'status')){
                $errorMessage .= ' Status:"'.$json->status.'"';
            }
            $this->error($errorMessage);
            return 4;
        }

        // compose data
        $items = $json->data->results;
        $sheetData = array();
        foreach ($items as $item){
            $sheetData[] = $this->getRow($item);
        }

        // Save the composed data
        $this->save($sheetData);

        return 0;
    }

    /**
     * Get row for obtained item.
     *
     * @param stdClass $item Decoded json of data got from the MARVEL API.
     * @return array
     */
    private function getRow($item){
        // find title
        $title = '';
        if (property_exists($item, 'title'))
            $title = $item->title;

        // find description
        $description = '';
        if (property_exists($item, 'description'))
            $description = $item->description;

        // find date first published
        $lowestDate = '';
        if ($this->argument('type') == 'Comic'){
            // Get the oldest relevant date
            $dates = $item->dates;
            $lowestDate = strtotime($item->modified);
            foreach($item->dates as $date){
                if(strtotime($date->date) < $lowestDate){
                    $lowestDate = strtotime($date->date);
                }
            }
            $lowestDate = date("Y-m-d H:i:s", $lowestDate);
        }
        elseif ($this->argument('type') == 'Event'){
            // Take start date
            $lowestDate = $item->start;
        }
        elseif ($this->argument('type') == 'Series'){
            // Take start year
            $lowestDate = $item->startYear.'-01-01 00:00:00';
        }
        elseif ($this->argument('type') == 'Story'){
            // Take whatever
            $lowestDate = $item->modified;
        }

        return array(
                'Character' => $this->argument('character'),
                'Data type' => $this->argument('type'),
                $this->argument('type').' name' => $title,
                $this->argument('type').' description' => $description,
                $this->argument('type').' date first published' => $lowestDate,
                );
    }

    /**
     * Compose complete parameters for API request.
     *
     * @param array $optional Parameters in addition to requred authentication parameters.
     * @return array Combined optional and required parameters.
     */
    private function getParams($optional){
        $publicKey = env('MARVEL_PUBLIC_KEY');
        $privateKey = env('MARVEL_PRIVATE_KEY');
        if (!$privateKey || !$publicKey){
            $this->error('Private or public key not set in environment (.env file), please register at developer.marvel.com');
        }
        //Get timestamp
        $ts = time();
        //Generate the hash
        $hash = md5($ts . $privateKey . $publicKey);

        return array_merge($optional,
            array(
            'apikey' => $publicKey,
            'ts' => $ts,
            'hash' => $hash,
        ));
    }

    /**
     * Save data to output file.
     *
     * @param array $sheetData Two dimensional array containing data to be saved into output file.
     * @return void
     */
    private function save($sheetData){
        $path = $this->argument('path');
        // File path is optional.
        if (!$path){
            // By default name the output file by characted and data type.
            // Use CSV format by default.
            $path = $this->argument('character').'_'.$this->argument('type').'.csv';
        }
        $path_parts = pathinfo($path);

        // Write data
        $doc = Excel::create($path_parts['filename']);
        $doc->sheet($this->argument('type'), function($sheet) use ($sheetData){
            $sheet->fromArray($sheetData);
        });

        // store file in specified format at specified directory.
        $doc->store($path_parts['extension'], $path_parts['dirname']);
    }
}
