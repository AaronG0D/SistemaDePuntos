<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { reactiveOmit } from '@vueuse/core'
import {
  SelectContent,
  type SelectContentEmits,
  type SelectContentProps,
  SelectPortal,
  SelectViewport,
  useForwardPropsEmits,
} from 'reka-ui'
import { cn } from '@/lib/utils'
import { SelectScrollDownButton, SelectScrollUpButton } from '.'
import { onBeforeUnmount } from 'vue'

defineOptions({
  inheritAttrs: false,
})

const props = withDefaults(
  defineProps<SelectContentProps & { class?: HTMLAttributes['class'] }>(),
  {
    position: 'popper',
  },
)
const emits = defineEmits<SelectContentEmits>()

const delegatedProps = reactiveOmit(props, 'class')
const forwarded = useForwardPropsEmits(delegatedProps, emits)

// Proteger el desmontaje del portal
onBeforeUnmount(() => {
  try {
    const portal = document.querySelector('[data-slot="select-content"]')?.parentElement
    if (portal?.parentElement) {
      portal.parentElement.removeChild(portal)
    }
  } catch (error) {
    console.debug('Error al desmontar portal de SelectContent:', error)
  }
})
</script>

<template>
  <SelectPortal>
    <SelectContent
      data-slot="select-content"
      v-bind="{ ...forwarded, ...$attrs }"
      :class="cn(
        'bg-white text-neutral-950 data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95 data-[side=bottom]:slide-in-from-top-2 data-[side=left]:slide-in-from-right-2 data-[side=right]:slide-in-from-left-2 data-[side=top]:slide-in-from-bottom-2 relative z-50 max-h-(--reka-select-content-available-height) min-w-[8rem] overflow-x-hidden overflow-y-auto rounded-md border border-neutral-200 shadow-md dark:bg-neutral-950 dark:text-neutral-50 dark:border-neutral-800',
        position === 'popper'
          && 'data-[side=bottom]:translate-y-1 data-[side=left]:-translate-x-1 data-[side=right]:translate-x-1 data-[side=top]:-translate-y-1',
        props.class,
      )"
    >
      <SelectViewport
        :class="cn(
          'p-1',
          position === 'popper' &&
            'h-(--reka-select-content-available-height) w-(--reka-select-content-available-width)',
        )"
      >
        <slot />
      </SelectViewport>

      <SelectScrollUpButton>
        <ChevronUp class="h-4 w-4" />
      </SelectScrollUpButton>

      <SelectScrollDownButton>
        <ChevronDown class="h-4 w-4" />
      </SelectScrollDownButton>
    </SelectContent>
  </SelectPortal>
</template>
