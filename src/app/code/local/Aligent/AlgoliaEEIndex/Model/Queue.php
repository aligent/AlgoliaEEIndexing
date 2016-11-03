<?php

class Aligent_AlgoliaEEIndex_Model_Queue extends Algolia_Algoliasearch_Model_Queue
{
    public function run($limit)
    {
        if(Mage::helper('aligent_algoliaeeindex')->shouldOverrideAlgoliaRunner()) {

            //Clear out any jobs higher then max retries.
            $this->db->delete($this->table, 'retries > max_retries');

            $full_reindex = ($limit === -1);
            $limit = $full_reindex ? 1 : $limit;

            $element_count = 0;
            $jobs = array();
            $offset = 0;
            $max_size = $this->config->getNumberOfElementByPage() * $limit;

            while ($element_count < $max_size) {
                $data = $this->db->query($this->db->select()->from($this->table, '*')->where('pid IS NULL')
                    ->order(array('job_id'))->limit($limit, $limit * $offset));
                $data = $data->fetchAll();

                $offset++;

                if (count($data) <= 0) {
                    break;
                }

                foreach ($data as $job) {
                    $job_size = (int) $job['data_size'];

                    if ($element_count + $job_size <= $max_size) {
                        $jobs[] = $job;
                        $element_count += $job_size;
                    } else {
                        break 2;
                    }
                }
            }

            if (count($jobs) <= 0) {
                return;
            }

            $first_id = $jobs[0]['job_id'];
            $last_id = $jobs[count($jobs) - 1]['job_id'];

            $pid = getmypid();

            // Reserve all new jobs since last run
            $this->db->query("UPDATE {$this->db->quoteIdentifier($this->table, true)} SET pid = ".$pid.' WHERE job_id >= '.$first_id." AND job_id <= $last_id");

            foreach ($jobs as &$job) {
                $job['data'] = json_decode($job['data'], true);
            }

            $jobs = $this->sortAndMergeJob($jobs);

            // Run all reserved jobs
            foreach ($jobs as $job) {
                try {
                    $model = Mage::getSingleton($job['class']);
                    $method = $job['method'];
                    $model->$method(new Varien_Object($job['data']));
                } catch (Exception $e) {

                    //Set the job ID back to null.
                    $this->db->query("UPDATE {$this->db->quoteIdentifier($this->table, true)} SET pid = NULL, retries = retries + 1 WHERE job_id = ".$job['job_id']);

                    $this->logger->log("Queue processing {$job['pid']} [KO]: Mage::getSingleton({$job['class']})->{$job['method']}(".json_encode($job['data']).')');
                    $this->logger->log(date('c').' ERROR: '.get_class($e).": '{$e->getMessage()}' in {$e->getFile()}:{$e->getLine()}\n"."Stack trace:\n".$e->getTraceAsString());
                }
            }

            // Delete only when finished to be able to debug the queue if needed
            $where = $this->db->quoteInto('pid = ?', $pid);
            $this->db->delete($this->table, $where);

            if ($full_reindex) {
                $this->run(-1);
            }
        } else {
            parent::run($limit);
        }
    }
}
