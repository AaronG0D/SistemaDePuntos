<script setup lang="ts">
import type { SwitchRootEmits, SwitchRootProps } from "reka-ui"
import type { HTMLAttributes } from "vue"
import { reactiveOmit } from "@vueuse/core"
import {
  SwitchRoot,

  SwitchThumb,
  useForwardPropsEmits,
} from "reka-ui"
import { cn } from "@/lib/utils"

const props = defineProps<SwitchRootProps & { class?: HTMLAttributes["class"] }>()

const emits = defineEmits<SwitchRootEmits>()

const delegatedProps = reactiveOmit(props, "class")

const forwarded = useForwardPropsEmits(delegatedProps, emits)
</script>

<template>
  <SwitchRoot
    data-slot="switch"
    v-bind="forwarded"
    :class="cn(
      'peer data-[state=checked]:bg-neutral-900 data-[state=unchecked]:bg-neutral-200 focus-visible:border-neutral-950 focus-visible:ring-neutral-950/50 dark:data-[state=unchecked]:bg-neutral-200/80 inline-flex h-[1.15rem] w-8 shrink-0 items-center rounded-full border border-neutral-200 border-transparent shadow-xs transition-all outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50 dark:data-[state=checked]:bg-neutral-50 dark:data-[state=unchecked]:bg-neutral-800 dark:focus-visible:border-neutral-300 dark:focus-visible:ring-neutral-300/50 dark:dark:data-[state=unchecked]:bg-neutral-800/80 dark:border-neutral-800',
      props.class,
    )"
  >
    <SwitchThumb
      data-slot="switch-thumb"
      :class="cn('bg-white dark:data-[state=unchecked]:bg-neutral-950 dark:data-[state=checked]:bg-neutral-50 pointer-events-none block size-4 rounded-full ring-0 transition-transform data-[state=checked]:translate-x-[calc(100%-2px)] data-[state=unchecked]:translate-x-0 dark:bg-neutral-950 dark:dark:data-[state=unchecked]:bg-neutral-50 dark:dark:data-[state=checked]:bg-neutral-900')"
    >
      <slot name="thumb" />
    </SwitchThumb>
  </SwitchRoot>
</template>
