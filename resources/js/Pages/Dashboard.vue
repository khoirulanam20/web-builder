<script setup>
import ProjectsIndex from './Projects/Index.vue';
</script>

<template>
    <ProjectsIndex :projects="$page.props.projects ?? []" />
</template>
