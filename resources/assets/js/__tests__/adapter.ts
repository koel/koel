import Vue from 'vue'
import { Wrapper as BaseWrapper, WrapperArray as BaseWrapperArray, VueClass } from '@vue/test-utils/types/index'
import { mount as baseMount, shallowMount, MountOptions } from '@vue/test-utils'

export interface Wrapper extends BaseWrapper<Vue> {
  readonly vm: Vue
  value: string
  has(what: any): boolean
  html(): string
  text(): string
  click(selector?: string, options?: any): Wrapper
  change(selector?: string): Wrapper
  dblclick(selector?: string): Wrapper
  submit (selector?: string): Wrapper
  find(any: any): Wrapper
  setValue(value: string): Wrapper
  input(selector?: string, options?: any): Wrapper
  blur(selector?: string): Wrapper
  hasAll(...args: any): Wrapper
  hasNone(...args: any): Wrapper
  findAll(...args: any): WrapperArray
  hasEmitted(event: string, data?: any): Wrapper
}

export interface WrapperArray extends BaseWrapperArray<Vue> {
  at(index: number): Wrapper
}

export const mount = (component: VueClass<Vue>, options: MountOptions<Vue> = {}): Wrapper => {
  return baseMount(component, options) as Wrapper
}

export const shallow = (component: VueClass<Vue>, options: MountOptions<Vue> = {}): Wrapper => {
  return shallowMount(component, options) as Wrapper
}
