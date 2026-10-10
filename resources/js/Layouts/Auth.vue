<template>
    <div class="wrapper auth-learning-shell">
		<slot/>
	</div>
</template>

<script>
    export default {
        // Default AppName
        data() {
            return { appName : "One Zero Learning" }
        }
    }
</script>

<style scoped>
.auth-learning-shell {
    min-height: 100vh;
    background:
        radial-gradient(circle at 12% 18%, rgba(35, 181, 173, 0.2), transparent 32rem),
        linear-gradient(145deg, #eefaf8 0%, #f7fbff 52%, #fff7ea 100%);
}
</style>
