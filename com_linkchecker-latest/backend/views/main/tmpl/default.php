<?php
/*
 * @copyright  Copyright (C) 2016 Marco Beierer. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die('Restricted access');
?>

<div class="bootstrap3" style="margin-top: 10px;">
	<?php if ($this->onLocalhost): ?>
		<div class="alert alert-error">
			It is not possible to use this plugin in a local development environment. The backend service needs to crawl your website and this is just possible if your site is reachable from the internet.
		</div>
	<?php endif; ?>

	<?php if ($this->showMultilangWarning): ?>
		<div class="alert alert-warning">
			<p>You are using the Link Checker on a multilingual website, have search engine friendly URLs enabled and using a language prefix for the default language. This means that the Link Checker would just check your default language site. If you like to check all language versions, you could use the <a href="https://www.marcobeierer.com/tools/link-checker-professional">Link Checker Professional</a>.</p>
		</div>
	<?php endif; ?>

	<?php if (count($this->websiteURLs) > 1): ?>
		<ul class="nav nav-tabs" role="tablist" style="margin-bottom: 20px;">
			<?php $firstWebsite = true; ?>
			<?php foreach ($this->websiteURLs as $websiteURL): ?>
				<li role="presentation" class="<?php if ($firstWebsite) { echo 'active'; } ?>">
					<a href="#<?php echo md5($websiteURL); ?>" aria-controls="<?php echo md5($websiteURL); ?>" role="tab" data-toggle="tab"><?php echo $websiteURL; ?></a>
				</li>
				<?php $firstWebsite = false; ?>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>

	<?php if (count($this->websiteURLs) > 1): ?>
		<div class="tab-content">
			<?php 
				$firstWebsite = true; 
				$count = 0; 
			?>
			<?php foreach ($this->websiteURLs as $websiteURL): ?>
				<div role="tabpanel" class="tab-pane <?php if ($firstWebsite) { echo 'active'; } ?>" id="<?php echo md5($websiteURL); ?>">
					<linkchecker
						id="<?php echo $count; ?>"
						website-url="<?php echo $websiteURL; ?>"
						token="<?php echo $this->token; ?>"
						origin-system="joomla"
						enable-scheduler="true"
						email="<?php echo $this->email; ?>"
						max-fetchers="<?php echo $this->maxFetchers; ?>"
					>
					</linkchecker>

					<?php if ($this->token != '' && false): // TODO disabled because included in tabs now ?>
						<hr />

						<h3>Scheduler</h3>
						<linkchecker-scheduler
							website-url="<?php echo $websiteURL; ?>"
							token="<?php echo $this->token; ?>"
							email="<?php echo $this->email; ?>">
						</linkchecker-scheduler>
					<?php endif; ?>
				</div>
				<?php 
					$firstWebsite = false; 
					$count++; 
				?>
			<?php endforeach; ?>
		</div>
	<?php else: ?>
		<linkchecker
			website-url="<?php echo $this->websiteURLs[0]; ?>"
			token="<?php echo $this->token; ?>"
			origin-system="joomla"
			enable-scheduler="true"
			email="<?php echo $this->email; ?>"
			max-fetchers="<?php echo $this->maxFetchers; ?>"
		>
		</linkchecker>

		<?php if ($this->token != '' && false): // TODO disabled ?>
			<hr />

			<h3>Scheduler</h3>
			<linkchecker-scheduler
				website-url="<?php echo $this->websiteURLs[0]; ?>"
				token="<?php echo $this->token; ?>"
				email="<?php echo $this->email; ?>">
			</linkchecker-scheduler>
		<?php endif; ?>
	<?php endif; ?>

	<hr />

	<?php if ($this->token == ''): ?>
		<h3>Professional Version</h3>
		<p>The Link Checker is also available as <a href="https://www.marcobeierer.com/tools/link-checker-professional">professional version</a>, which additionally checks your site for <strong>broken images</strong>, comes with a scheduler for <strong>automatic daily checks</strong> and has improved support for <strong>multilingual sites</strong>.</p>
	<?php endif; ?>

	<h3>Credits</h3>
	<p>The Link Checker for Joomla is developed and maintained by <a href="https://www.marcobeierer.com">Marco Beierer</a> and is also available as <a href="https://www.marcobeierer.com/tools/link-checker">online tool</a> and as <a href="https://www.marcobeierer.com/wordpress-plugins/link-checker">WordPress plugin</a>.</p>
</div>
