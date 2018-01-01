import Component from '@/components/shared/overlay.vue'
import SoundBar from '@/components/shared/sound-bar.vue' 

describe('components/shared/overlay', () => {
  it('shows with default options', async done => {
    const wrapper = mount(Component)
    await wrapper.vm.show()
    wrapper.contains(SoundBar).should.be.true
    wrapper.contains('button.btn-dismiss').should.be.false
    done()
  })

  it('allows option overriding', async done => {
    const wrapper = mount(Component)
    await wrapper.vm.show({ 
      dismissable: true, 
      type: 'warning',
      message: 'Foo'
    })
    wrapper.contains(SoundBar).should.be.false
    wrapper.contains('button.btn-dismiss').should.be.true
    wrapper.find('span.message').html().should.contain('Foo')
    done()
  })
  
  it('hides', async done => {
    const wrapper = mount(Component)
    await wrapper.vm.show()
    wrapper.contains('.display').should.be.true
    await wrapper.vm.hide()
    wrapper.contains('.display').should.be.false
    done()
  })

  it('dismisses', async done => {
    const wrapper = mount(Component)
    await wrapper.vm.show({ dismissable: true })
    wrapper.contains('.display').should.be.true
    wrapper.find('button.btn-dismiss').trigger('click')
    wrapper.contains('.display').should.be.false
    done()
  })
})
