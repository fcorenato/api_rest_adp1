<div class="preloader-background">
	<img class="responsive-img" width="250px" src="img/vetromani-logo.png" alt="big7">
	&nbsp;&nbsp;&nbsp;
<div class="preloader-wrapper big active">
	<div class="spinner-layer spinner-teal-only">
		<div class="circle-clipper left">
			<div class="circle"></div>
		</div>
		<div class="gap-patch">
			<div class="circle"></div>
		</div>
		<div class="circle-clipper right">
			<div class="circle"></div>
		</div>
	</div>
</div>
</div>
<script>
	//PRELOADPAGE
	document.addEventListener("DOMContentLoaded", function(){
	$('.preloader-background').delay(1500).fadeOut('slow');
	
	$('.preloader-wrapper')
		.delay(1500)
		.fadeOut();
	});
</script>