<?php

if ( ! class_exists( 'CSLFW_Cargo_Job' ) ) {
	abstract class CSLFW_Cargo_Job {

		private $attempts = 0;

		/**
		 * @param $attempts
		 */
		public function set_attempts( $attempts ) {
			$this->attempts = (int) $attempts;
		}

		/**
		 * @return int
		 */
		public function get_attempts( ) {
			return $this->attempts;
		}

		/**
		 * @param int $delay
		 */
		public function retry( $delay = 30 ) {
			$job = $this;
			if (null == $job->attempts) $job->set_attempts(0);
			$job->set_attempts($job->get_attempts() + 1);
            cslfw_push_cargo_job($job, $delay);
		}

		/**
		 * @return $this
		 */
		protected function applyRateLimitedScenario()
		{
            cslfw_cargo_set_transient('api-rate-limited', true );

			$this->retry();

			return $this;
		}

		/**
		 * Handle the job.
		 */
		abstract public function handle();

        abstract public function toArgsArray();
	}
}
