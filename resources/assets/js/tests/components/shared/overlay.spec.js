import Component from '@/components/shared/overlay.vue'
import SoundBar from '@/components/shared/sound-bar.vue' 

describe('components/shared/overlay', () => {
  it('shows with default options', async done => {
    const wrapper = mount(Component)
    await wrapper.vm.show()
    wrapper.has(SoundBar).should.be.true
    wrapper.has('button.btn-dismiss').should.be.false
    done()
  })

  it('allows option overriding', async done => {
    const wrapper = mount(Component)
    await wrapper.vm.show({ 
      dismissable: true, 
      type: 'warning',
      message: 'Foo'
    })
    wrapper.has(SoundBar).should.be.false
    wrapper.has('button.btn-dismiss').should.be.true
    wrapper.find('span.message').html().should.contain('Foo')
    done()
  })
  
  it('hides', async done => {
    const wrapper = mount(Component)
    await wrapper.vm.show()
    wrapper.has('.display').should.be.true
    await wrapper.vm.hide()
    wrapper.has('.display').should.be.false
    done()
  })

  it('dismisses', async done => {
    const wrapper = mount(Component)
    await wrapper.vm.show({ dismissable: true })
    wrapper.has('.display').should.be.true
    wrapper.click('button.btn-dismiss')
    wrapper.has('.display').should.be.false
    done()
  })
})
