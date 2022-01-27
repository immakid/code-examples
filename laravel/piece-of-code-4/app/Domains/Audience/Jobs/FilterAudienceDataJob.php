<?php

namespace App\Domains\Audience\Jobs;

use Lucid\Foundation\Job;
use Vinelab\Bowler\Connection;
use Vinelab\Bowler\Producer;

class FilterAudienceDataJob extends Job
{
    private $talent;
    private $translatedData;
    private $fetchedAt;

    public function __construct($talent, $translatedData, $fetchedAt)
    {
        $this->talent = $talent;
        $this->translatedData = $translatedData;
        $this->fetchedAt = $fetchedAt;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $audienceData = [];
        $demographicsData = ['Location: by country', 'Age - Nielsen', 'Gender', 'Likes & interests'];
        $menaCountries = ['MA', 'EG', 'SA', 'IQ', 'KW', 'AE', 'BH', 'OM', 'JO', 'LB', 'QA', 'TN', 'DZ', 'TR'];

        foreach ($this->translatedData as $row) {
            foreach ($row->section_rows as $sectionRow) {
                if (in_array($row->section_name, $demographicsData)) {
                    if ($row->section_name == 'Location: by country') {
                        if (in_array($sectionRow->title, $menaCountries)) {
                            array_push($audienceData, [
                                'talent_id' => $this->talent['talent_id'],
                                'platform_id' => (string) $this->talent['platform_id'],
                                $this->translateSectionName($row->section_name) => $sectionRow->title,
                                'percentage' => $sectionRow->pct,
                                'fetched_at' => $this->fetchedAt,
                            ]);
                        }
                    } else {
                        array_push($audienceData, [
                            'talent_id' => $this->talent['talent_id'],
                            'platform_id' => (string) $this->talent['platform_id'],
                            $this->translateSectionName($row->section_name) => $sectionRow->title,
                            'percentage' => $sectionRow->pct,
                            'fetched_at' => $this->fetchedAt,
                        ]);
                    }
                }
            }
        }

        return $audienceData;
    }

    private function translateSectionName($value)
    {
        switch ($value) {
            case 'Location: by country':
                return "country";
                break;
            case 'Age - Nielsen':
                return "age";
                break;
            case 'Gender':
                return "gender";
                break;
            case 'Likes & interests':
                return "interest";
                break;
        }
    }

}
