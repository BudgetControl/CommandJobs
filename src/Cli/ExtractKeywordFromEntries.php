<?php

declare(strict_types=1);

namespace Budgetcontrol\jobs\Cli;

use DonatelloZa\RakePlus\RakePlus;
use Illuminate\Support\Facades\Log;
use Budgetcontrol\jobs\Cli\JobCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Budgetcontrol\Library\Model\Entry;
use Budgetcontrol\Library\Model\EntryKeywords;
use Carbon\Carbon;

class ExtractKeywordFromEntries extends JobCommand
{

    protected string $command = 'core:extract-keywords';
    const USER_EMAIL = 'demo@budgetcontrol.cloud';
    const USER_PASSWORD = 'BY3PIViM-4ieFGm';

    private int $chunkSize = 250;

    public function __construct()
    {
        parent::__construct();
        
        if(env('CHUNK_SIZE')) {
            $this->chunkSize = (int) env('CHUNK_SIZE');
        }
    }

    public function configure()
    {
        $this->setName($this->command)
            ->setDescription('Extract keywords from entries')
            ->addArgument('action', InputArgument::OPTIONAL, 'Action to perform', 'clear')
            ->setHelp("This command will remove all data from the database");
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // every minuts will get 250 entries to process
        Entry::where('has_keywords', '=', false)
            ->chunk($this->chunkSize, function ($entries) {
                foreach ($entries as $entry) {

                    $stringKeys = [];
                    if(!empty($entry->note)) {
                        $keywords = RakePlus::create($entry->note);
                        $scores = $keywords->scores();
                        $stringKeys = $keywords->get();
                    }
                    
                    if (count($stringKeys) > 0) {

                        //if already exist records we need to delete them
                        EntryKeywords::where('entry_id', $entry->id)->delete();

                        foreach ($stringKeys as $key) {
                            EntryKeywords::create([
                                'entry_id' => $entry->id,
                                'keyword' => $key,
                                'score' => $scores[$key] ?? 0
                            ]);
                        }
                        Log::info("Entry ID {$entry->id} keywords extracted and saved.");
                    }

                    $entry->has_keywords = true;
                    $entry->save();
                }
            });

        $output->writeln('Job completed');
        Log::info('Keywords extraction completed.');
        return Command::SUCCESS;

    }
}