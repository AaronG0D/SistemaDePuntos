<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardHeader, CardContent, CardTitle } from '@/components/ui/card';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';

// Props que debe enviar el backend:
// estadisticas: {
//   depositos_hoy, depositos_semana, depositos_mes,
//   puntos_hoy, puntos_semana, puntos_mes,
//   variacion_mes, top_estudiantes, ranking_curso, ranking_paralelo
// }

const props = defineProps<{
  estadisticas: {
    depositos_hoy: number;
    depositos_semana: number;
    depositos_mes: number;
    puntos_hoy: number;
    puntos_semana: number;
    puntos_mes: number;
    variacion_mes: number;
    top_estudiantes: Array<{ nombre: string; puntos: number }>;
    ranking_curso: Array<{ curso: string; puntos: number }>;
    ranking_paralelo: Array<{ paralelo: string; puntos: number }>;
  }
}>();
</script>

<template>
  <Head title="Dashboard" />
  <AppLayout>
    <div class="grid gap-4 md:grid-cols-4">
      <Card>
        <CardHeader>
          <CardTitle>Depósitos hoy</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="text-2xl font-bold">{{ props.estadisticas.depositos_hoy }}</div>
        </CardContent>
      </Card>
      <Card>
        <CardHeader>
          <CardTitle>Depósitos semana</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="text-2xl font-bold">{{ props.estadisticas.depositos_semana }}</div>
        </CardContent>
      </Card>
      <Card>
        <CardHeader>
          <CardTitle>Depósitos mes</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="text-2xl font-bold">{{ props.estadisticas.depositos_mes }}</div>
          <div class="text-xs" :class="props.estadisticas.variacion_mes > 0 ? 'text-green-600' : 'text-red-600'">
            {{ props.estadisticas.variacion_mes > 0 ? '+' : '' }}{{ props.estadisticas.variacion_mes }}% respecto al mes anterior
          </div>
        </CardContent>
      </Card>
      <Card>
        <CardHeader>
          <CardTitle>Puntos este mes</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="text-2xl font-bold">{{ props.estadisticas.puntos_mes }}</div>
        </CardContent>
      </Card>
    </div>

    <div class="mt-8 grid gap-6 md:grid-cols-2">
      <Card>
        <CardHeader>
          <CardTitle>Top 10 estudiantes</CardTitle>
        </CardHeader>
        <CardContent>
          <ol>
            <li v-for="(est, i) in props.estadisticas.top_estudiantes" :key="est.nombre">
              {{ i + 1 }}. {{ est.nombre }} - {{ est.puntos }} pts
            </li>
          </ol>
        </CardContent>
      </Card>
      <Card>
        <CardHeader>
          <CardTitle>Ranking por curso</CardTitle>
        </CardHeader>
        <CardContent>
          <ol>
            <li v-for="(curso, i) in props.estadisticas.ranking_curso" :key="curso.curso">
              {{ i + 1 }}. {{ curso.curso }} - {{ curso.puntos }} pts
            </li>
          </ol>
        </CardContent>
      </Card>
      <Card>
        <CardHeader>
          <CardTitle>Ranking por paralelo</CardTitle>
        </CardHeader>
        <CardContent>
          <ol>
            <li v-for="(paralelo, i) in props.estadisticas.ranking_paralelo" :key="paralelo.paralelo">
              {{ i + 1 }}. {{ paralelo.paralelo }} - {{ paralelo.puntos }} pts
            </li>
          </ol>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
