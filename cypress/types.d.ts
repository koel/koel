declare namespace Cypress {
  interface Chainable {
    $login(redirectTo?: string): Chainable<AUTWindow>
    $loginAsNonAdmin(redirectTo?: string): Chainable<AUTWindow>
    $each(dataset: Array<Array<any>>, callback: Function): void
    $confirm(): void
    $clickSidebarItem(sidebarItemText: string): Chainable<JQuery>

    /**
     * Mock audio playback, including intercepting the media request, album thumbnail, media info etc.
     */
    $mockPlayback(): void

    /**
     * Queue several songs from the All Song screen.
     * @param count
     */
    $queueSeveralSongs(count?: number): void

    $assertPlaylistSongCount(name: string, count: number): void
    $assertFavoriteSongCount(count: number): void

    /**
     * Support finding an element within an element identified with a test ID.
     * For example, given a DOM like this:
     *   <form data-testid="my-form">
     *     <input name="email">
     *   </form>
     * then the input can be accessed with:
     *   cy.$findInTestId('my-form input[name=email]')
     * which is identical to
     *   cy.findByTestId('my-form').find('input[name=email]')
     */
    $findInTestId<E extends Node = HTMLElement>(selector: string): Chainable<JQuery<E>>
  }
}
