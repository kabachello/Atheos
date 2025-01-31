<?php


trait Status {

	public function repoStatus() {
		$result = $this->execute("git status --branch --porcelain");

		if ($result["code"] !== 0) {
			Common::send($result);
		}

		$result = $this->parseChanges($result["text"]);
		$status = "Unknown";

		if (!empty($result["added"]) ||
			!empty($result["deleted"]) ||
			!empty($result["modified"]) ||
			!empty($result["renamed"])) {
			$status = 'Uncommitted';
		} else if (!empty($result["untracked"])) {
			$status = 'Untracked';
		} else {
			$status = 'Committed';
		}

		Common::send("success", $status);
	}

	public function fileStatus($path) {
		if (!file_exists($path)) {
			Common::send("error", i18n("path_missing"));
		}

		$dirname = dirname($path);
		$filename = basename($path);

		if (!is_dir($dirname)) {
			Common::send("error", "Invalid path.");
		}

		chdir($dirname);

		$result = $this->execute("git diff --numstat " . $filename);

		if ($result["code"] !== 0) {
			Common::send($result);
		} else {
			$result = $result["text"];
		}

		if (count($result) > 0 && $result[0] !== "") {
			$stats = explode("\t", $result[0]);
			$additions = $stats[0] ? $stats[0] : 0;
			$deletions = $stats[1] ? $stats[1] : 0;

		} else {
			$result = $this->execute("git status --branch --porcelain");

			if ($result["code"] === 0 && !empty($result["text"])) {

				$status = $this->parseChanges($result["text"]);

				if (in_array($filename, $status['untracked'])) {
					$file = file_get_contents($filename);
					$file = explode("\n", $file);
					$additions = count($file);
					$deletions = 0;
				}
			}

			$additions = 0;
			$deletions = 0;
		}
		$result = array("branch" => $this->getCurrentBranch(), "insertions" => $additions, "deletions" => $deletions);
		Common::send("success", $result);
	}

	public function branchStatus($repo) {
		$result = $this->execute("git status --branch --porcelain");
		if ($result["code"] !== 0) return false;
		$result = $result["text"];

		debug($result);

		preg_match('/(?<=\[).+?(?=\])/', $result[0], $status);

		if (!is_array($status) || empty($status)) {
			return i18n("git_status_current");
		}


		$int = (int)preg_replace("/(ahead|behind)/", "", $status[0]);
		$count = $int === 1 ? "single" : "plural";

		if (strpos($status[0], "ahead") !== false) {
			$status = i18n("git_status_ahead_$count", $int);
		} elseif (strpos($status[0], "behind") !== false) {
			$status = i18n("git_status_behind_$count", $int);
		}

		return $status;

	}

	public function loadChanges($repo) {
		$result = $this->execute("git status --branch --porcelain");
		if ($result["code"] !== 0) {
			$result = $this->parseChanges($result["text"]);
		} else {
			$result = i18n("codegit_error_statusFail");
		}
		return $result;
	}
}